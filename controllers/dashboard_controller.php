<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin();

$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$meta_calorias  = (int) ($_SESSION['meta_calorias'] ?? 2000);
$meta_proteina  = 150;
$meta_carbs     = 220;
$meta_grasas    = 70;

// ─ Fecha y saludo dinámico ────────────────────────────────────────────
date_default_timezone_set('America/Tijuana');
$hora_actual = (int) date('H');
if ($hora_actual < 12)      $saludo = 'Buenos días';
elseif ($hora_actual < 19)  $saludo = 'Buenas tardes';
else                        $saludo = 'Buenas noches';

$dias_es_corto  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$meses_es_corto = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$label_hoy = $dias_es_corto[(int)date('w')] . ', ' . (int)date('j') . ' ' . $meses_es_corto[(int)date('n') - 1];

// ─── Consulta real: SUM de macros del día ─────────────────────────
require_once '../config/conexion.php';
require_once '../models/Diario.php';
require_once '../models/Receta.php';

$id_usuario = (int) $_SESSION['usuario_id'];
$fecha_hoy  = date('Y-m-d');

$macros = Diario::getDailyMacros($conn, $id_usuario, $fecha_hoy);

$calorias_consumidas = (float) $macros['total_cal'];
$pro_consumidas      = (float) $macros['total_pro'];
$carbs_consumidas    = (float) $macros['total_carbs'];
$grasas_consumidas   = (float) $macros['total_grasas'];

$calorias_restantes = max(0, $meta_calorias - $calorias_consumidas);
$porcentaje_anillo  = ($meta_calorias > 0) ? min(100, ($calorias_consumidas / $meta_calorias) * 100) : 0;
$pro_p    = ($meta_proteina > 0) ? min(100, ($pro_consumidas   / $meta_proteina) * 100) : 0;
$carbs_p  = ($meta_carbs    > 0) ? min(100, ($carbs_consumidas / $meta_carbs)    * 100) : 0;
$grasas_p = ($meta_grasas   > 0) ? min(100, ($grasas_consumidas/ $meta_grasas)   * 100) : 0;

$racha = Diario::getStreak($conn, $id_usuario, $meta_calorias);

// ─── LÓGICA DE SUGERENCIA DINÁMICA DE RECETA ──────────────────────
$lowest_macro = min($pro_p, $carbs_p, $grasas_p);
$keyword = "";

if ($lowest_macro == $pro_p) {
    $keyword = '+Proteína';
} elseif ($lowest_macro == $carbs_p) {
    $keyword = '+Carbs';
} else {
    $keyword = 'Keto'; // Para grasas bajas asume Keto u otra
}

$receta_sugerida = Receta::getSuggestedRecipe($conn, $keyword);

