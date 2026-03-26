<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (!$data || !isset($data['calorias'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit();
}

// Inicializar contenedores de sesión si no existen
if (!isset($_SESSION['macros_consumidos'])) {
    $_SESSION['macros_consumidos'] = [
        'calorias' => 0,
        'proteina' => 0,
        'carbs' => 0,
        'grasas' => 0
    ];
}

// Acumular los datos confirmados desde la IA
$_SESSION['macros_consumidos']['calorias'] += (int)$data['calorias'];
$_SESSION['macros_consumidos']['proteina'] += (int)($data['proteina'] ?? 0);
$_SESSION['macros_consumidos']['carbs'] += (int)($data['carbs'] ?? 0);
$_SESSION['macros_consumidos']['grasas'] += (int)($data['grasas'] ?? 0);

echo json_encode(['status' => 'success', 'message' => 'Guardado en sesión']);
?>
