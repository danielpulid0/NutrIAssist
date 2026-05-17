<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin(true);
Auth::requirePost();

$json_input = file_get_contents('php://input');
$datos      = json_decode($json_input, true);

if (!$datos || !isset($datos['alimento'], $datos['calorias'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit();
}

require_once '../config/conexion.php';
require_once '../models/Comida.php';

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

// ── VALIDACIONES MATEMÁTICAS ─────

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
$tipo_final = Comida::normalizarTipoComida($datos['tipo_comida'] ?? 'snack');
try {

    $item = [
        'nombre'   => $nombre_ia,
        'calorias' => $calorias,
        'proteina' => $proteina,
        'carbs'    => $carbs,
        'grasas'   => $grasas
    ];

    $id_comida = Comida::saveFoodLog($conn, $id_usuario, $fecha, $tipo_final, [$item]);

    echo json_encode(['status' => 'success', 'message' => 'Alimento guardado']);

} catch (PDOException $e) {
    error_log('[guardar_comida_manual] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos']);
}
?>
