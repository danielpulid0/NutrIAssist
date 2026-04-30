<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../config/conexion.php';

$id_usuario = (int) $_SESSION['usuario_id'];
$fecha_fin = date('Y-m-d');
$fecha_inicio = date('Y-m-d', strtotime('-6 days')); // Last 7 days including today

try {
    $stmt = $conn->prepare("
        SELECT 
            rd.fecha,
            COALESCE(SUM(ac.calorias_ia), 0) AS cals,
            COALESCE(SUM(ac.proteina_ia), 0) AS pro,
            COALESCE(SUM(ac.carbs_ia), 0) AS car,
            COALESCE(SUM(ac.grasas_ia), 0) AS gra
        FROM Registros_Diarios rd
        LEFT JOIN Comidas c ON rd.id_registro = c.id_registro
        LEFT JOIN Alimentos_Consumidos ac ON c.id_comida = ac.id_comida
        WHERE rd.id_usuario = :uid AND rd.fecha BETWEEN :inicio AND :fin
        GROUP BY rd.fecha
        ORDER BY rd.fecha ASC
    ");
    $stmt->execute([
        ':uid' => $id_usuario,
        ':inicio' => $fecha_inicio,
        ':fin' => $fecha_fin
    ]);
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
