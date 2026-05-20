<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin();

require_once '../config/conexion.php';

$meta_calorias = (int) ($_SESSION['meta_calorias'] ?? 2000);
$meta_calorias = (int) ($_SESSION['meta_calorias'] ?? 2000);
$fecha_hoy     = date('Y-m-d');

// ─── Fecha seleccionada (GET o hoy) ───────────────────────────────
$fecha_sel = $_GET['fecha'] ?? $fecha_hoy;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_sel) || $fecha_sel > $fecha_hoy) {  //prueba 1 y 2
    $fecha_sel = $fecha_hoy;
}

$ts_sel   = strtotime($fecha_sel);
$dia_sel  = (int) date('j', $ts_sel);
$mes_sel  = (int) date('n', $ts_sel);
$anio_sel = (int) date('Y', $ts_sel);
$es_hoy   = ($fecha_sel === $fecha_hoy);

$meses_es = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
             'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$dias_cortos = ['D','L','M','M','J','V','S'];
$label_mes = $meses_es[$mes_sel - 1] . ' ' . $anio_sel;

// Semana que contiene la fecha seleccionada (Dom → Sáb)
$dow_sel       = (int) date('w', $ts_sel); // 0=Dom
$inicio_semana = date('Y-m-d', strtotime("-{$dow_sel} days", $ts_sel));

// Semana anterior y siguiente (para navegación)
$semana_prev = date('Y-m-d', strtotime('-7 days', $ts_sel));
$semana_next = date('Y-m-d', strtotime('+7 days', $ts_sel));
if ($semana_next > $fecha_hoy) $semana_next = null;              //test 3

// Label de la fecha
$dias_es = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$label_fecha = $es_hoy
    ? 'Hoy'
    : $dias_es[$dow_sel] . ', ' . $dia_sel . ' de ' . $meses_es[$mes_sel - 1];

// ─── Consultas DB ─────────────────────────────────────────────────
require_once '../models/Diario.php';

$macros = Diario::getDailyMacros($conn, $id_usuario, $fecha_sel);
$total_calorias = (float) $macros['total_cal'];

$comidas_grupos = Diario::getFoodLogGroups($conn, $id_usuario, $fecha_sel);
$racha = Diario::getStreak($conn, $id_usuario, $meta_calorias);

$tipos_orden = ['Desayuno', 'Comida', 'Cena', 'Snack'];
