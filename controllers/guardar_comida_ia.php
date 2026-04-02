<?php
// ============================================================
// controllers/guardar_comida_ia.php
// Recibe el JSON confirmado del chat y realiza los INSERTs
// en Registros_Diarios → Comidas → Alimentos_Consumidos.
// Usa el patrón Snapshot: guarda los macros directamente
// tal como los calculó la IA (columnas _ia).
// ============================================================
session_start();
header('Content-Type: application/json');

// 1. Seguridad
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

// 2. Leer y validar datos del chat
$json_input = file_get_contents('php://input');
$datos      = json_decode($json_input, true);

if (!$datos || !isset($datos['calorias'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos recibidos']);
    exit();
}

// 3. Sanear valores
$id_usuario  = (int) $_SESSION['usuario_id'];
$nombre_ia   = htmlspecialchars(trim($datos['alimento']    ?? 'Alimento'));
$descripcion = htmlspecialchars(trim($datos['descripcion'] ?? ''));
$calorias    = (int)   ($datos['calorias']  ?? 0);
$proteina    = (float) ($datos['proteina']  ?? 0);
$carbs       = (float) ($datos['carbs']     ?? 0);
$grasas      = (float) ($datos['grasas']    ?? 0);
$fecha_hoy   = date('Y-m-d');

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

// 5. Conectar a la DB
require_once '../config/conexion.php';

try {
    // ─── Paso A: Garantizar el registro diario de hoy ─────────────────
    $stmt = $conn->prepare("
        INSERT IGNORE INTO Registros_Diarios (id_usuario, fecha)
        VALUES (:uid, :fecha)
    ");
    $stmt->execute([':uid' => $id_usuario, ':fecha' => $fecha_hoy]);

    // Obtener id del registro de hoy
    $stmt = $conn->prepare("
        SELECT id_registro FROM Registros_Diarios
        WHERE id_usuario = :uid AND fecha = :fecha
        LIMIT 1
    ");
    $stmt->execute([':uid' => $id_usuario, ':fecha' => $fecha_hoy]);
    $id_registro = $stmt->fetchColumn();

    // ─── Paso B: Crear el bloque de comida ────────────────────────────
    $stmt = $conn->prepare("
        INSERT INTO Comidas (id_registro, tipo)
        VALUES (:id_registro, :tipo)
    ");
    $stmt->execute([
        ':id_registro' => $id_registro,
        ':tipo'        => $tipo_final,
    ]);
    $id_comida = $conn->lastInsertId();

    // ─── Paso C: Guardar el alimento con patrón Snapshot ──────────────
    // id_alimento = NULL (viene de IA, no del catálogo)
    // cantidad_gramos = 0 (no aplica para registros de IA)
    $stmt = $conn->prepare("
        INSERT INTO Alimentos_Consumidos
            (id_comida, id_alimento, cantidad_gramos,
             nombre_ia, calorias_ia, proteina_ia, carbs_ia, grasas_ia)
        VALUES
            (:id_comida, NULL, 0,
             :nombre_ia, :calorias, :proteina, :carbs, :grasas)
    ");
    $stmt->execute([
        ':id_comida' => $id_comida,
        ':nombre_ia' => $nombre_ia,
        ':calorias'  => $calorias,
        ':proteina'  => $proteina,
        ':carbs'     => $carbs,
        ':grasas'    => $grasas,
    ]);

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
