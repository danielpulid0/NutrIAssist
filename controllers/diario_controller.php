<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}

require_once '../config/conexion.php';

$id_usuario    = (int) $_SESSION['usuario_id'];
$meta_calorias = (int) ($_SESSION['meta_calorias'] ?? 2000);
$fecha_hoy     = date('Y-m-d');

// ─── Fecha seleccionada (GET o hoy) ───────────────────────────────
$fecha_sel = $_GET['fecha'] ?? $fecha_hoy;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_sel) || $fecha_sel > $fecha_hoy) {
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
if ($semana_next > $fecha_hoy) $semana_next = null;

// Label de la fecha
$dias_es = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$label_fecha = $es_hoy
    ? 'Hoy'
    : $dias_es[$dow_sel] . ', ' . $dia_sel . ' de ' . $meses_es[$mes_sel - 1];

// ─── Consultas DB ─────────────────────────────────────────────────
$total_calorias = 0;
try {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(ac.calorias_ia), 0) AS total
        FROM Alimentos_Consumidos ac
        INNER JOIN Comidas c ON ac.id_comida = c.id_comida
        INNER JOIN Registros_Diarios rd ON c.id_registro = rd.id_registro
        WHERE rd.id_usuario = :uid AND rd.fecha = :fecha
    ");
    $stmt->execute([':uid' => $id_usuario, ':fecha' => $fecha_sel]);
    $total_calorias = (float) $stmt->fetchColumn();
} catch (PDOException $e) { error_log('[diario] ' . $e->getMessage()); }

$comidas_grupos = [];
try {
    $stmt = $conn->prepare("
        SELECT c.tipo AS tipo_comida,
               ac.id_consumo,
               ac.nombre_ia  AS nombre,
               ac.calorias_ia AS calorias,
               ac.proteina_ia AS proteina,
               ac.carbs_ia    AS carbs,
               ac.grasas_ia   AS grasas
        FROM Alimentos_Consumidos ac
        INNER JOIN Comidas c ON ac.id_comida = c.id_comida
        INNER JOIN Registros_Diarios rd ON c.id_registro = rd.id_registro
        WHERE rd.id_usuario = :uid AND rd.fecha = :fecha
        ORDER BY c.id_comida ASC, ac.id_consumo ASC
    ");
    $stmt->execute([':uid' => $id_usuario, ':fecha' => $fecha_sel]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $comidas_grupos[$row['tipo_comida']][] = $row;
    }
} catch (PDOException $e) { error_log('[diario] ' . $e->getMessage()); }

$tipos_orden = ['Desayuno', 'Comida', 'Cena', 'Snack'];
