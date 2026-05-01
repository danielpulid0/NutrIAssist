<?php
// ============================================================
// controllers/guardar_comida_manual.php
// Recibe un alimento ingresado manualmente en el diario
// y lo persiste en la DB para la fecha indicada.
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$json_input = file_get_contents('php://input');
$datos      = json_decode($json_input, true);

if (!$datos || !isset($datos['alimento'], $datos['calorias'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit();
}

require_once '../config/conexion.php';

$id_usuario  = (int) $_SESSION['usuario_id'];
$fecha_hoy   = date('Y-m-d');

// Validar y sanear la fecha — no permitir fechas futuras
$fecha_raw = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha'] ?? '')
             ? $datos['fecha']
             : $fecha_hoy;
if ($fecha_raw > $fecha_hoy) { $fecha_raw = $fecha_hoy; }
$fecha = $fecha_raw;

// Sanear valores
$nombre_ia = htmlspecialchars(trim($datos['alimento']));
$calorias  = (int)   ($datos['calorias'] ?? 0);
$proteina  = (float) ($datos['proteina'] ?? 0);
$carbs     = (float) ($datos['carbs']    ?? 0);
$grasas    = (float) ($datos['grasas']   ?? 0);

// ── VALIDACIONES MATEMÁTICAS (Seguridad de Lógica de Negocio) ────────
// Regla 1: Valores nunca negativos
if ($calorias < 0 || $proteina < 0 || $carbs < 0 || $grasas < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Los valores nutricionales no pueden ser negativos.']);
    exit();
}
// Regla 2: Límites fisiológicos máximos razonables
// (ningún alimento en 100g tiene más de 900 kcal ni 100g de macronutriente)
if ($calorias > 9000) {
    echo json_encode(['status' => 'error', 'message' => 'El valor de calorías supera el límite permitido (9000 kcal).']);
    exit();
}
if ($proteina > 100 || $carbs > 100 || $grasas > 100) {
    echo json_encode(['status' => 'error', 'message' => 'Los macronutrientes no pueden superar 100g por registro.']);
    exit();
}
// Regla 3: Nombre requerido
if (empty($nombre_ia)) {
    echo json_encode(['status' => 'error', 'message' => 'Nombre y calorías son requeridos']);
    exit();
}

// Normalizar tipo de comida → proteger el ENUM
$tipo_ia  = ucfirst(strtolower($datos['tipo_comida'] ?? 'snack'));
$mapa_tipos = [
    'Desayuno' => 'Desayuno', 'Almuerzo' => 'Comida', 'Comida' => 'Comida',
    'Cena' => 'Cena', 'Snack' => 'Snack', 'Merienda' => 'Snack', 'Postre' => 'Snack',
];
$tipo_final = $mapa_tipos[$tipo_ia] ?? 'Snack';
try {
    // Paso A: Garantizar registro diario
    $conn->prepare("INSERT IGNORE INTO Registros_Diarios (id_usuario, fecha) VALUES (?,?)")
         ->execute([$id_usuario, $fecha]);

    $stmt = $conn->prepare("SELECT id_registro FROM Registros_Diarios WHERE id_usuario=? AND fecha=? LIMIT 1");
    $stmt->execute([$id_usuario, $fecha]);
    $id_registro = $stmt->fetchColumn();

    // Paso B: Crear bloque de comida
    $stmt = $conn->prepare("INSERT INTO Comidas (id_registro, tipo) VALUES (?,?)");
    $stmt->execute([$id_registro, $tipo_final]);
    $id_comida = $conn->lastInsertId();

    // Paso C: Guardar alimento con patrón Snapshot
    $stmt = $conn->prepare("
        INSERT INTO Alimentos_Consumidos
            (id_comida, id_alimento, cantidad_gramos, nombre_ia, calorias_ia, proteina_ia, carbs_ia, grasas_ia)
        VALUES
            (?, NULL, 0, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$id_comida, $nombre_ia, $calorias, $proteina, $carbs, $grasas]);

    echo json_encode(['status' => 'success', 'message' => 'Alimento guardado']);

} catch (PDOException $e) {
    error_log('[guardar_comida_manual] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos']);
}
?>
