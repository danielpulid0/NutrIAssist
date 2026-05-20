<?php
// ============================================================
// controllers/guardar_comida_lista.php
// Recibe una lista de alimentos con sus gramos y los macros
// ya calculados por el frontend (macro_por_100g / 100 * g).
// Crea 1 registro en Comidas y N filas en Alimentos_Consumidos.
// ============================================================
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin(true);
Auth::requirePost();

$json_input = file_get_contents('php://input');
$datos      = json_decode($json_input, true);

// ── Validar estructura básica ─────────────────────────────────────
if (!$datos || empty($datos['items']) || !is_array($datos['items'])) {
    echo json_encode(['status' => 'error', 'message' => 'Lista de alimentos vacía o inválida.']);
    exit();
}

if (count($datos['items']) > 10) {
    echo json_encode(['status' => 'error', 'message' => 'No se pueden registrar más de 10 alimentos a la vez.']);
    exit();
}

require_once '../config/conexion.php';
require_once '../models/Comida.php';

$fecha_hoy  = date('Y-m-d');

// Validar y sanear fecha
$fecha_raw = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha'] ?? '')
             ? $datos['fecha']
             : $fecha_hoy;
if ($fecha_raw > $fecha_hoy) { $fecha_raw = $fecha_hoy; }
$fecha = $fecha_raw;

// Normalizar tipo de comida → proteger el ENUM de MySQL
$tipo_final = Comida::normalizarTipoComida($datos['tipo_comida'] ?? 'snack');

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
    if ($calorias > 2000) {
        echo json_encode(['status' => 'error', 'message' => "Calorías de \"$nombre\" superan el límite (2000 kcal)."]);
        exit();
    }

    $items_saneados[] = compact('nombre','gramos','calorias','proteina','carbs','grasas','id_alim');
}

try {

    $id_comida = Comida::saveFoodLog($conn, $id_usuario, $fecha, $tipo_final, $items_saneados);

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
