<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: recetas.php");
    exit();
}

require_once '../config/conexion.php';
$id_receta = (int) $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT * FROM Recetas WHERE id_receta = :id");
    $stmt->bindParam(':id', $id_receta, PDO::PARAM_INT);
    $stmt->execute();
    $receta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$receta) { header("Location: recetas.php"); exit(); }

    $stmt_ing = $conn->prepare("
        SELECT ir.id_ingrediente, ir.cantidad_gramos, a.nombre,
               ROUND(a.calorias_por_100g * ir.cantidad_gramos / 100) AS calorias_calc,
               ROUND(a.proteina_por_100g * ir.cantidad_gramos / 100, 1) AS proteina_calc
        FROM Ingredientes_Receta ir
        JOIN Alimentos a ON ir.id_alimento = a.id_alimento
        WHERE ir.id_receta = :id
    ");
    $stmt_ing->bindParam(':id', $id_receta, PDO::PARAM_INT);
    $stmt_ing->execute();
    $ingredientes = $stmt_ing->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de Base de Datos: " . $e->getMessage());
}

// Parsear instrucciones en pasos numerados
$pasos = [];
if (!empty($receta['instrucciones'])) {
    $partes = preg_split('/\d+\.\s+/', $receta['instrucciones'], -1, PREG_SPLIT_NO_EMPTY);
    foreach ($partes as $p) {
        $paso = trim($p);
        if ($paso) $pasos[] = $paso;
    }
}

// Extraer costo de etiquetas
$tags  = json_decode($receta['etiquetas'] ?? '[]', true) ?: [];
$costo = 'Medio';
foreach ($tags as $t) {
    if (in_array($t, ['Económico', 'Medio', 'Caro', 'Premium'])) { $costo = $t; break; }
}

$tiempo_txt = $receta['tiempo_prep_min'] ? $receta['tiempo_prep_min'] . ' min' : '—';
