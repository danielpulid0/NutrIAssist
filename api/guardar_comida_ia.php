<?php
// ============================================================
// controllers/guardar_comida_ia.php
// Recibe el JSON confirmado del chat y realiza los INSERTs
// en Registros_Diarios → Comidas → Alimentos_Consumidos.
// Usa el patrón Snapshot: guarda los macros directamente
// tal como los calculó la IA (columnas _ia).
// ============================================================
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin(true);
Auth::requirePost();

// 2. Leer y validar datos del chat
$json_input = file_get_contents('php://input');
$datos      = json_decode($json_input, true);

if (!$datos || !isset($datos['calorias'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos recibidos']);
    exit();
}

// 3. Conectar a la DB (Esto establece el timezone correcto: America/Tijuana)
require_once '../config/conexion.php';

// 4. Sanear valores — strip any non-numeric chars (AI sometimes sends "350 kcal" or "25g")
$nombre_ia   = htmlspecialchars(trim($datos['alimento']    ?? 'Alimento'));
$descripcion = htmlspecialchars(trim($datos['descripcion'] ?? ''));
$calorias    = (int)   preg_replace('/[^0-9.]/', '', (string)($datos['calorias']  ?? '0'));
$proteina    = (float) preg_replace('/[^0-9.]/', '', (string)($datos['proteina']  ?? '0'));
$carbs       = (float) preg_replace('/[^0-9.]/', '', (string)($datos['carbs']     ?? '0'));
$grasas      = (float) preg_replace('/[^0-9.]/', '', (string)($datos['grasas']    ?? '0'));
$fecha_hoy   = date('Y-m-d');

// Log para depuración
error_log("[guardar_comida_ia] Datos recibidos: usuario=$id_usuario, alimento=$nombre_ia, cal=$calorias, pro=$proteina, carbs=$carbs, grasas=$grasas");

// 4. Normalizar tipo de comida → proteger el ENUM de MySQL
$tipo_ia  = ucfirst(strtolower($datos['tipo_comida'] ?? 'snack'));
$mapa_tipos = [
    'Desayuno' => 'Desayuno',
    'Almuerzo' => 'Comida',
    'Comida'   => 'Comida',
    'Cena'     => 'Cena',
    'Snack'    => 'Snack',
    'Merienda' => 'Snack',
    'Postre'   => 'Snack',
    'Sugerencia' => 'Snack', // Caso E de la IA
];
$tipo_final = $mapa_tipos[$tipo_ia] ?? 'Snack';

try {
    require_once '../models/Comida.php';

    $item = [
        'nombre'   => $nombre_ia,
        'calorias' => $calorias,
        'proteina' => $proteina,
        'carbs'    => $carbs,
        'grasas'   => $grasas
    ];

    $id_comida = Comida::saveFoodLog($conn, $id_usuario, $fecha_hoy, $tipo_final, [$item]);

    // ─── Paso D: Actualizar sesión para respuesta inmediata ───────────
    if (!isset($_SESSION['macros_consumidos'])) {
        $_SESSION['macros_consumidos'] = ['calorias' => 0, 'proteina' => 0, 'carbs' => 0, 'grasas' => 0];
    }
    $_SESSION['macros_consumidos']['calorias'] += $calorias;
    $_SESSION['macros_consumidos']['proteina'] += $proteina;
    $_SESSION['macros_consumidos']['carbs']    += $carbs;
    $_SESSION['macros_consumidos']['grasas']   += $grasas;

    echo json_encode(['status' => 'success', 'message' => 'Comida guardada correctamente']);

} catch (PDOException $e) {
    error_log('[guardar_comida_ia] ERROR: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar en la base de datos']);
}
?>
