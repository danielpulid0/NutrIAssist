<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin();
if (!isset($_GET['id'])) {
    header("Location: recetas.php");
    exit();
}

require_once '../config/conexion.php';
require_once '../models/Receta.php';

$id_receta = (int) $_GET['id'];

$receta = Receta::getById($conn, $id_receta);
if (!$receta) {
    header("Location: recetas.php");
    exit();
}

$ingredientes = Receta::getIngredients($conn, $id_receta);

// Parsear instrucciones en pasos numerados
$pasos = [];
if (!empty($receta['instrucciones'])) {
    $partes = preg_split('/\d+\.\s+/', $receta['instrucciones'], -1, PREG_SPLIT_NO_EMPTY);
    foreach ($partes as $parte_paso) {
        $paso = trim($parte_paso);
        if ($paso) $pasos[] = $paso;
    }
}

// Extraer costo de etiquetas
$tags  = json_decode($receta['etiquetas'] ?? '[]', true) ?: [];
$costo = 'Medio';
foreach ($tags as $tag_actual) {
    if (in_array($tag_actual, ['Económico', 'Medio', 'Caro', 'Premium'])) { $costo = $tag_actual; break; }
}

$tiempo_txt = $receta['tiempo_prep_min'] ? $receta['tiempo_prep_min'] . ' min' : '—';
