<?php
// ============================================================
// controllers/guardar_comida_lista.php
// Recibe una lista de alimentos con sus gramos y los macros
// ya calculados por el frontend (macro_por_100g / 100 * g).
// Crea 1 registro en Comidas y N filas en Alimentos_Consumidos.
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$json_input = file_get_contents('php://input');
$datos      = json_decode($json_input, true);

// ── Validar estructura básica ─────────────────────────────────────
if (!$datos || empty($datos['items']) || !is_array($datos['items'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lista de alimentos vacía o inválida.']);
    exit();
}

if (count($datos['items']) > 20) {
    echo json_encode(['status' => 'error', 'message' => 'No se pueden registrar más de 20 alimentos a la vez.']);
    exit();
}

$id_usuario = (int) $_SESSION['usuario_id'];
$fecha_hoy  = date('Y-m-d');

// Validar y sanear fecha
$fecha_raw = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha'] ?? '')
             ? $datos['fecha']
             : $fecha_hoy;
if ($fecha_raw > $fecha_hoy) { $fecha_raw = $fecha_hoy; }
$fecha = $fecha_raw;

// Normalizar tipo de comida → proteger el ENUM de MySQL
$tipo_raw = ucfirst(strtolower($datos['tipo_comida'] ?? 'snack'));
$mapa_tipos = [
    'Desayuno' => 'Desayuno', 'Almuerzo' => 'Comida', 'Comida'  => 'Comida',
    'Cena'     => 'Cena',     'Snack'    => 'Snack',  'Merienda'=> 'Snack',
];
$tipo_final = $mapa_tipos[$tipo_raw] ?? 'Snack';

// ── Validar cada ítem antes de tocar la DB ────────────────────────
$items_saneados = [];
foreach ($datos['items'] as $idx => $item) {
    $nombre    = htmlspecialchars(trim($item['nombre'] ?? ''));
    $gramos    = (float) ($item['gramos']      ?? 0);
    $calorias  = (float) ($item['calorias_ia'] ?? 0);
    $proteina  = (float) ($item['proteina_ia'] ?? 0);
    $carbs     = (float) ($item['carbs_ia']    ?? 0);
    $grasas    = (float) ($item['grasas_ia']   ?? 0);
    $id_alim   = isset($item['id_alimento']) && $item['id_alimento'] ? (int)$item['id_alimento'] : null;

    if (empty($nombre)) {
        echo json_encode(['status' => 'error', 'message' => "El ítem #".($idx+1)." no tiene nombre."]);
        exit();
    }
    if ($gramos <= 0) {
        echo json_encode(['status' => 'error', 'message' => "La cantidad de \"$nombre\" debe ser mayor a 0g."]);
        exit();
    }
    // Validaciones matemáticas — no permitir trampas desde la consola
    if ($calorias < 0 || $proteina < 0 || $carbs < 0 || $grasas < 0) {
        echo json_encode(['status' => 'error', 'message' => "Los valores de \"$nombre\" no pueden ser negativos."]);
        exit();
    }
    if ($calorias > 9000) {
        echo json_encode(['status' => 'error', 'message' => "Calorías de \"$nombre\" superan el límite (9000 kcal)."]);
        exit();
    }

    $items_saneados[] = compact('nombre','gramos','calorias','proteina','carbs','grasas','id_alim');
}

// ── Persistir en la DB ────────────────────────────────────────────
require_once '../config/conexion.php';

try {
    // Paso A: Garantizar el registro diario
    $conn->prepare("INSERT IGNORE INTO Registros_Diarios (id_usuario, fecha) VALUES (?,?)")
         ->execute([$id_usuario, $fecha]);

    $stmt = $conn->prepare("SELECT id_registro FROM Registros_Diarios WHERE id_usuario=? AND fecha=? LIMIT 1");
    $stmt->execute([$id_usuario, $fecha]);
    $id_registro = $stmt->fetchColumn();

    // Paso B: Crear bloque de comida
    $stmt = $conn->prepare("INSERT INTO Comidas (id_registro, tipo) VALUES (?,?)");
    $stmt->execute([$id_registro, $tipo_final]);
    $id_comida = $conn->lastInsertId();

    // Paso C: Insertar cada alimento con patrón Snapshot
    $stmtItem = $conn->prepare("
        INSERT INTO Alimentos_Consumidos
            (id_comida, id_alimento, cantidad_gramos, nombre_ia, calorias_ia, proteina_ia, carbs_ia, grasas_ia)
        VALUES
            (:id_comida, :id_alim, :gramos, :nombre, :cal, :prot, :carbs, :gras)
    ");

    foreach ($items_saneados as $it) {
        $stmtItem->execute([
            ':id_comida' => $id_comida,
            ':id_alim'   => $it['id_alim'],
            ':gramos'    => $it['gramos'],
            ':nombre'    => $it['nombre'],
            ':cal'       => $it['calorias'],
            ':prot'      => $it['proteina'],
            ':carbs'     => $it['carbs'],
            ':gras'      => $it['grasas'],
        ]);
    }

    echo json_encode([
        'status'  => 'success',
        'message' => 'Comida registrada correctamente',
        'total_items' => count($items_saneados),
    ]);

} catch (PDOException $e) {
    error_log('[guardar_comida_lista] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos.']);
}
?>
