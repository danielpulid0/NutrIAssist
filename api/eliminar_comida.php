<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin(true);
Auth::requirePost();

$json_input = file_get_contents('php://input');
$datos = json_decode($json_input, true);

if (!isset($datos['id_consumo'])) {
    echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado']);
    exit();
}

$id_consumo = (int) $datos['id_consumo'];

require_once '../config/conexion.php';

try {
    // Verificar propiedad
    $stmt_check = $conn->prepare("
        SELECT ac.id_consumo 
        FROM Alimentos_Consumidos ac
        INNER JOIN Comidas c ON ac.id_comida = c.id_comida
        INNER JOIN Registros_Diarios rd ON c.id_registro = rd.id_registro
        WHERE ac.id_consumo = :id_consumo AND rd.id_usuario = :uid
    ");
    $stmt_check->execute([':id_consumo' => $id_consumo, ':uid' => $id_usuario]);
    
    if (!$stmt_check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
        exit();
    }

    // Eliminar
    $stmt_del = $conn->prepare("DELETE FROM Alimentos_Consumidos WHERE id_consumo = :id");
    $stmt_del->execute([':id' => $id_consumo]);

    echo json_encode(['status' => 'success', 'message' => 'Alimento eliminado']);
} catch (PDOException $e) {
    error_log('[eliminar_comida] DB ERROR: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
}
?>
