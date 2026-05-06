<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin();

require_once '../config/conexion.php';
require_once '../models/Receta.php';

// ─── Búsqueda / filtro por GET ─────────────────────────────────────────
$q          = trim($_GET['q'] ?? '');
$filtro_tag = trim($_GET['tag'] ?? '');

$recetas_raw = Receta::search($conn, $q);

// ─── Helpers ───────────────────────────────────────────────────────────
// Colores de tags conocidos
$tag_colores = [
    'Fácil'          => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
    'Vegetariano'    => ['bg' => '#DBEAFE', 'fg' => '#2563EB'],
    'Vegano'         => ['bg' => '#DBEAFE', 'fg' => '#2563EB'],
    'Keto'           => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
    '+Proteína'      => ['bg' => '#FEF3C7', 'fg' => '#D97706'],
    'Alta Proteína'  => ['bg' => '#FEF3C7', 'fg' => '#D97706'],
    'Bebida'         => ['bg' => '#F3E8FF', 'fg' => '#9333EA'],
    'Fibra'          => ['bg' => '#FFEDD5', 'fg' => '#EA580C'],
    'Snack'          => ['bg' => '#FCE7F3', 'fg' => '#DB2777'],
    'Favoritos'      => ['bg' => '#FEF3C7', 'fg' => '#D97706'],
    'Rápido'         => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
    'Premium'        => ['bg' => '#EDE9FE', 'fg' => '#7C3AED'],
    'Pre-Entreno'    => ['bg' => '#FFEDD5', 'fg' => '#EA580C'],
    'Equilibrado'    => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
    'Bajo en Carbs'  => ['bg' => '#DBEAFE', 'fg' => '#2563EB'],
    'Omega-3'        => ['bg' => '#DBEAFE', 'fg' => '#2563EB'],
    'Crujiente'      => ['bg' => '#FFEDD5', 'fg' => '#EA580C'],
    'Económico'      => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
];

$costos        = ['Económico', 'Medio', 'Caro', 'Premium'];
$tipos_comida  = ['Desayuno', 'Comida', 'Cena', 'Snack', 'Vegetariano', 'Vegano'];
$todos_tags    = [];

// Procesamos recetas y extraemos todos los tags para los chips de filtro
$recetas = [];
foreach ($recetas_raw as $r) {
    $tags = json_decode($r['etiquetas'] ?? '[]', true) ?: [];

    // Detectar costo en etiquetas
    $costo_txt = 'Medio';
    foreach ($tags as $t) {
        if (in_array($t, $costos)) { $costo_txt = $t; break; }
    }

    // Tag visible: el primero que no sea tipo de comida ni costo
    $tag_visible = null;
    $color_tag   = ['bg' => '#F3F4F6', 'fg' => '#6B7280'];
    foreach ($tags as $t) {
        if (!in_array($t, $costos) && !in_array($t, $tipos_comida)) {
            $tag_visible = $t;
            $color_tag   = $tag_colores[$t] ?? $color_tag;
            break;
        }
    }

    // Acumular tags únicos para el chip-bar de filtros
    foreach ($tags as $t) { $todos_tags[$t] = true; }

    // Aplicar filtro de tag si hay uno activo
    if ($filtro_tag !== '' && !in_array($filtro_tag, $tags)) continue;

    $recetas[] = array_merge($r, [
        'tags'       => $tags,
        'costo_txt'  => $costo_txt,
        'tag_visible'=> $tag_visible,
        'color_tag'  => $color_tag,
    ]);
}

$todos_tags = array_keys($todos_tags);
