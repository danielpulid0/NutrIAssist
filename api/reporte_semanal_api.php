<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin(true);

require_once '../config/conexion.php';
require_once '../models/Diario.php';
$fecha_fin = date('Y-m-d');
$fecha_inicio = date('Y-m-d', strtotime('-6 days')); // Last 7 days including today

try {
    $resultados = Diario::getWeeklyReport($conn, $id_usuario, $fecha_inicio, $fecha_fin);
    
    // Rellenar días faltantes con 0
    $dias = [];
    $current = strtotime($fecha_inicio);
    $end = strtotime($fecha_fin);
    while ($current <= $end) {
        $dStr = date('Y-m-d', $current);
        $dias[$dStr] = ['fecha' => $dStr, 'cals' => 0, 'pro' => 0, 'car' => 0, 'gra' => 0];
        $current = strtotime('+1 day', $current);
    }
    
    foreach ($resultados as $row) {
        if (isset($dias[$row['fecha']])) {
            $dias[$row['fecha']] = [
                'fecha' => $row['fecha'],
                'cals' => (float)$row['cals'],
                'pro' => (float)$row['pro'],
                'car' => (float)$row['car'],
                'gra' => (float)$row['gra']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'datos' => array_values($dias),
        'meta' => $_SESSION['meta_calorias'] ?? 2000,
        'nombre' => $_SESSION['nombre_usuario'] ?? 'Usuario'
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de base de datos', 'details' => $e->getMessage()]);
}
