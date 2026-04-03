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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist – Diario</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        .mobile-container { background-color: var(--color-bg-app); padding-bottom: 90px; }

        /* ── TÍTULO ── */
        .page-title-center {
            text-align: center; font-size: 1.2rem; font-weight: 700;
            color: var(--color-text-dark); margin-bottom: 1rem;
        }

        /* ── CALENDARIO SEMANAL ── */
        .calendar-card {
            background: #fff; border-radius: 16px;
            padding: 0.9rem 1rem 1rem; margin-bottom: 0.85rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .cal-header {
            display: flex; align-items: center;
            margin-bottom: 0.8rem; gap: 0.5rem;
        }
        .cal-prev-mes {
            background: none; border: none; cursor: pointer;
            color: var(--color-text-gray); font-size: 1.3rem;
            line-height: 1; padding: 0 0.2rem; flex-shrink: 0;
            transition: color 0.15s;
        }
        .cal-prev-mes:hover { color: var(--color-text-dark); }
        .cal-mes-label {
            font-size: 0.9rem; font-weight: 700;
            color: var(--color-text-dark);
        }

        /* Semana deslizable */
        .cal-week-wrapper {
            overflow: hidden;
            touch-action: pan-y; /* permite scroll vertical pero capturamos horizontal */
        }
        .cal-week-track {
            display: flex;
            transition: transform 0.3s ease;
            /* Mostramos 3 semanas pero solo la central es visible */
            will-change: transform;
        }
        .cal-week-slide {
            min-width: 100%;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        .cal-week-slide.labels {
            margin-bottom: 0.35rem;
        }
        .cal-day-label {
            text-align: center; font-size: 0.7rem;
            font-weight: 500; color: var(--color-text-gray);
            padding: 0.15rem 0;
        }
        .cal-day {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 0.25rem 0; cursor: pointer;
        }
        .cal-day-num {
            width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.88rem; color: var(--color-text-dark);
            transition: background 0.15s;
        }
        .cal-day:not(.future):not(.empty) .cal-day-num:hover {
            background: var(--color-mint);
        }
        .cal-day.active .cal-day-num {
            background: var(--color-malachite); color: #fff; font-weight: 700;
        }
        .cal-day.today:not(.active) .cal-day-num {
            border: 2px solid var(--color-malachite);
            color: var(--color-malachite); font-weight: 700;
        }
        .cal-day.future .cal-day-num { color: #D1D5DB; cursor: default; }
        .cal-day.empty { cursor: default; }
        .cal-dot {
            width: 4px; height: 4px; border-radius: 50%;
            background: var(--color-malachite); margin-top: 2px;
        }
        .cal-day.active .cal-dot { background: rgba(255,255,255,0.7); }
        .cal-day.future .cal-dot { display: none; }

        /* ── CALORÍAS ── */
        .cals-card {
            background: #fff; border-radius: 16px;
            padding: 1.1rem 1.25rem; margin-bottom: 0.85rem;
            display: flex; align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .cals-date-label { font-size: 0.72rem; color: var(--color-text-gray); margin-bottom: 2px; }
        .cals-label { font-size: 0.78rem; color: var(--color-text-gray); margin-bottom: 0.3rem; }
        .cals-num { font-size: 1.55rem; font-weight: 800; color: var(--color-text-dark); letter-spacing: -0.02em; }
        .cals-meta { font-size: 0.85rem; color: var(--color-text-gray); }
        .cals-flame-ring {
            width: 50px; height: 50px; border-radius: 50%;
            border: 2.5px solid var(--color-malachite);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.35rem; flex-shrink: 0;
        }

        /* ── MEAL CARDS ── */
        .meal-card {
            background: #fff; border-radius: 16px;
            margin-bottom: 0.75rem; overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .meal-header {
            display: flex; align-items: center;
            padding: 1rem 1.1rem; gap: 0.85rem;
            user-select: none;
        }
        .meal-header.clickable { cursor: pointer; }
        .meal-icon-bubble {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .meal-icon-bubble svg { width: 22px; height: 22px; }
        .meal-text { flex: 1; min-width: 0; }
        .meal-name { font-size: 0.98rem; font-weight: 700; color: var(--color-text-dark); margin-bottom: 2px; }
        .meal-kcal { font-size: 0.8rem; color: var(--color-text-gray); }
        .meal-pending-label { font-size: 0.8rem; color: var(--color-text-gray); }
        .meal-actions { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }

        /* Botón + */
        .btn-add-item {
            width: 28px; height: 28px; border-radius: 50%;
            border: 1.5px solid var(--color-border);
            background: none; cursor: pointer; color: var(--color-text-gray);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; line-height: 1;
            transition: border-color 0.15s, color 0.15s, background 0.15s;
            font-family: 'Inter', sans-serif;
        }
        .btn-add-item:hover {
            border-color: var(--color-malachite);
            color: var(--color-malachite);
            background: var(--color-mint);
        }

        /* Chevron */
        .meal-chevron { color: var(--color-text-gray); transition: transform 0.25s ease; }
        .meal-chevron.up { transform: rotate(180deg); }

        /* Contenido colapsable */
        .meal-content { max-height: 0; overflow: hidden; transition: max-height 0.35s ease; }
        .meal-content.open { max-height: 600px; }
        .meal-divider { height: 1px; background: var(--color-border); }
        .meal-item {
            display: flex; align-items: center;
            padding: 0.6rem 1.25rem; gap: 0.7rem;
        }
        .meal-item-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--color-text-gray); flex-shrink: 0;
        }
        .meal-item-name { font-size: 0.88rem; color: var(--color-text-dark); flex: 1; font-weight: 500; }
        .meal-item-meta { font-size: 0.78rem; color: var(--color-text-gray); white-space: nowrap; }

        /* ── MODAL REGISTRO MANUAL ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.45); z-index: 1000;
            align-items: flex-end; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-sheet {
            background: #fff; border-radius: 20px 20px 0 0;
            width: 100%; max-width: 480px;
            padding: 1.5rem 1.25rem 2rem;
            animation: slideUp 0.28s ease;
            max-height: 90vh; overflow-y: auto;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .modal-title {
            font-size: 1.05rem; font-weight: 700; color: var(--color-text-dark);
            margin-bottom: 1.1rem; display: flex;
            justify-content: space-between; align-items: center;
        }
        .modal-close {
            background: none; border: none; font-size: 1.5rem;
            cursor: pointer; color: var(--color-text-gray); line-height: 1;
        }
        /* Vistas internas del modal */
        .modal-view { display: none; }
        .modal-view.active { display: block; }

        /* ── PASO 1: Constructor de lista ── */
        .add-row {
            display: grid;
            grid-template-columns: 1fr 90px auto;
            gap: 0.5rem; align-items: start;
            margin-bottom: 0.5rem;
        }
        .add-row-labels {
            display: grid;
            grid-template-columns: 1fr 90px auto;
            gap: 0.5rem; margin-bottom: 0.25rem;
        }
        .add-row-labels span {
            font-size: 0.72rem; font-weight: 600; color: var(--color-text-gray);
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        /* campo de búsqueda */
        .search-wrap { position: relative; }
        .search-wrap input {
            width: 100%; padding: 0.7rem 0.9rem;
            border: 1px solid var(--color-border); border-radius: 10px;
            font-size: 0.92rem; font-family: 'Inter', sans-serif;
            color: var(--color-text-dark); outline: none;
            transition: border-color 0.15s; box-sizing: border-box;
        }
        .search-wrap input:focus { border-color: var(--color-malachite); }
        /* campo cantidad */
        .qty-input {
            width: 100%; padding: 0.7rem 0.6rem;
            border: 1px solid var(--color-border); border-radius: 10px;
            font-size: 0.92rem; font-family: 'Inter', sans-serif;
            color: var(--color-text-dark); outline: none; text-align: center;
            transition: border-color 0.15s; box-sizing: border-box;
        }
        .qty-input:focus { border-color: var(--color-malachite); }
        /* botón agregar */
        .btn-add-alim {
            height: 42px; padding: 0 0.8rem;
            background: var(--color-malachite); color: #fff;
            border: none; border-radius: 10px; cursor: pointer;
            font-size: 1.3rem; font-weight: 700; line-height: 1;
            font-family: 'Inter', sans-serif;
            transition: background 0.15s, opacity 0.15s;
            white-space: nowrap;
        }
        .btn-add-alim:hover:not(:disabled) { background: #0db844; }
        .btn-add-alim:disabled { background: #D1D5DB; cursor: not-allowed; }
        /* dropdown autocompletado */
        .ac-dropdown {
            display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
            background: #fff; border: 1px solid var(--color-border);
            border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            z-index: 300; overflow: hidden;
        }
        .ac-dropdown.open { display: block; }
        .ac-item {
            padding: 0.7rem 0.9rem; cursor: pointer;
            display: flex; align-items: center; gap: 0.55rem;
            transition: background 0.12s;
        }
        .ac-item:hover { background: #F0FFF4; }
        .ac-item-icon { font-size: 1.05rem; flex-shrink: 0; }
        .ac-item-info { flex: 1; min-width: 0; }
        .ac-item-name { font-size: 0.88rem; font-weight: 600; color: var(--color-text-dark); }
        .ac-item-sub  { font-size: 0.73rem; color: var(--color-text-gray); }
        .ac-badge {
            font-size: 0.62rem; padding: 0.12rem 0.45rem; border-radius: 99px;
            font-weight: 700; flex-shrink: 0;
        }
        .ac-badge.local { background: #DCFCE7; color: #16A34A; }
        .ac-badge.usda  { background: #DBEAFE; color: #1D4ED8; }
        .ac-status {
            padding: 0.7rem 0.9rem; font-size: 0.82rem;
            color: var(--color-text-gray); text-align: center;
        }
        .ac-status a { color: var(--color-malachite); font-weight: 600; text-decoration: none; }
        /* lista de items agregados */
        .items-list {
            margin: 0.8rem 0 0; min-height: 0;
        }
        .items-list-empty {
            text-align: center; font-size: 0.82rem;
            color: var(--color-text-gray); padding: 0.8rem 0;
        }
        .item-row {
            display: flex; align-items: center;
            padding: 0.55rem 0;
            border-bottom: 1px solid var(--color-border);
            gap: 0.5rem;
        }
        .item-row:last-child { border-bottom: none; }
        .item-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--color-malachite); flex-shrink: 0;
        }
        .item-name {
            flex: 1; font-size: 0.88rem;
            font-weight: 500; color: var(--color-text-dark);
            min-width: 0; overflow: hidden;
            text-overflow: ellipsis; white-space: nowrap;
        }
        .item-qty {
            font-size: 0.8rem; color: var(--color-text-gray);
            white-space: nowrap; flex-shrink: 0;
        }
        .btn-remove-item {
            background: none; border: none; cursor: pointer;
            color: #9CA3AF; font-size: 1rem; line-height: 1;
            padding: 0 0.1rem; flex-shrink: 0;
            transition: color 0.15s;
        }
        .btn-remove-item:hover { color: #EF4444; }
        /* botón registrar */
        .btn-registrar {
            width: 100%; margin-top: 1.1rem;
            background: var(--color-malachite); color: #fff;
            border: none; border-radius: 99px;
            padding: 0.9rem; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; font-family: 'Inter', sans-serif;
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
            transition: background 0.15s;
        }
        .btn-registrar:hover:not(:disabled) { background: #0db844; }
        .btn-registrar:disabled { background: #D1D5DB; cursor: not-allowed; }

        /* ── PASO 2: Vista previa ── */
        .preview-header {
            font-size: 0.8rem; color: var(--color-text-gray);
            margin-bottom: 0.85rem;
        }
        .preview-table-wrap { overflow-x: auto; margin-bottom: 1rem; }
        .preview-table {
            width: 100%; border-collapse: collapse;
            font-size: 0.82rem;
        }
        .preview-table th {
            text-align: left; padding: 0.4rem 0.5rem;
            font-size: 0.7rem; font-weight: 700;
            color: var(--color-text-gray);
            text-transform: uppercase; letter-spacing: 0.04em;
            border-bottom: 2px solid var(--color-border);
        }
        .preview-table th:not(:first-child) { text-align: right; }
        .preview-table td {
            padding: 0.55rem 0.5rem;
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text-dark);
            vertical-align: middle;
        }
        .preview-table td:not(:first-child) { text-align: right; }
        .preview-table .td-name {
            font-weight: 500; max-width: 130px;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .preview-table .td-qty { color: var(--color-text-gray); white-space: nowrap; }
        .preview-table tr.total-row td {
            font-weight: 800; border-top: 2px solid var(--color-border);
            border-bottom: none; color: var(--color-malachite);
            font-size: 0.88rem;
        }
        .preview-table tr.total-row .td-name { color: var(--color-text-dark); }
        /* acciones preview */
        .preview-actions {
            display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;
            margin-top: 0.5rem;
        }
        .btn-editar {
            background: #F3F4F6; color: var(--color-text-dark);
            border: none; border-radius: 99px;
            padding: 0.85rem; font-size: 0.9rem; font-weight: 700;
            cursor: pointer; font-family: 'Inter', sans-serif;
            transition: background 0.15s;
        }
        .btn-editar:hover { background: #E5E7EB; }
        .btn-confirmar {
            background: var(--color-malachite); color: #fff;
            border: none; border-radius: 99px;
            padding: 0.85rem; font-size: 0.9rem; font-weight: 700;
            cursor: pointer; font-family: 'Inter', sans-serif;
            transition: background 0.15s;
        }
        .btn-confirmar:hover:not(:disabled) { background: #0db844; }
        .btn-confirmar:disabled { background: #9CA3AF; cursor: not-allowed; }

        /* ── TOAST ── */
        .toast {
            position: fixed; bottom: 100px; left: 50%;
            transform: translateX(-50%);
            background: #1F2937; color: #fff;
            padding: 0.65rem 1.2rem; border-radius: 99px;
            font-size: 0.85rem; font-weight: 500; z-index: 400;
            opacity: 0; transition: opacity 0.3s; pointer-events: none;
            white-space: nowrap;
        }
        .toast.show { opacity: 1; }
    </style>
</head>
<body>

<div class="mobile-container">

    <h1 class="page-title-center">Diario de Comidas</h1>

    <!-- ── CALENDARIO SEMANAL ── -->
    <div class="calendar-card">
        <div class="cal-header">
            <button class="cal-prev-mes" id="btn-prev-mes" aria-label="Mes anterior">&#8249;</button>
            <span class="cal-mes-label" id="cal-mes-label"><?= $label_mes ?></span>
        </div>

        <!-- Etiquetas de días (fijas) -->
        <div style="display:grid; grid-template-columns:repeat(7,1fr); margin-bottom:0.3rem;">
            <?php foreach ($dias_cortos as $d): ?>
                <div class="cal-day-label"><?= $d ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Track deslizable (se llena con JS) -->
        <div class="cal-week-wrapper" id="cal-week-wrapper">
            <div class="cal-week-track" id="cal-week-track">
                <!-- semana anterior | semana actual | semana siguiente -->
                <div class="cal-week-slide" id="slide-prev"></div>
                <div class="cal-week-slide" id="slide-curr"></div>
                <div class="cal-week-slide" id="slide-next"></div>
            </div>
        </div>
    </div>

    <!-- CALORÍAS TOTALES -->
    <div class="cals-card">
        <div>
            <div class="cals-date-label"><?= htmlspecialchars($label_fecha) ?></div>
            <div class="cals-label">Calorías Totales</div>
            <div>
                <span class="cals-num"><?= number_format($total_calorias) ?></span>
                <span class="cals-meta"> / <?= number_format($meta_calorias) ?> kcal</span>
            </div>
        </div>
        <div class="cals-flame-ring">🔥</div>
    </div>

    <!-- ── MEAL CARDS DINÁMICAS ── -->
    <?php
    $meal_cfg = [
        'Desayuno' => ['bg' => '#FFF3E0',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/></svg>'],
        'Comida'   => ['bg' => '#F0FFF4',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="#11CF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>'],
        'Cena'     => ['bg' => '#EDE9FE',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>'],
        'Snack'    => ['bg' => '#FFF0F6',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="#EC4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="9" cy="10" r="1" fill="#EC4899"/><circle cx="14" cy="9" r="1" fill="#EC4899"/><circle cx="10" cy="14" r="1" fill="#EC4899"/><circle cx="15" cy="14" r="1" fill="#EC4899"/></svg>'],
    ];

    foreach ($tipos_orden as $tipo):
        $alimentos   = $comidas_grupos[$tipo] ?? [];
        $tiene_datos = count($alimentos) > 0;
        $total_tipo  = round(array_sum(array_column($alimentos, 'calorias')));
        $cfg         = $meal_cfg[$tipo];
        $safe_id     = strtolower($tipo);
        $es_snack    = ($tipo === 'Snack');

        // Lógica de botones (la regla de negocio central):
        // - Snack: siempre muestra +; también muestra chevron si tiene datos
        // - Resto: solo muestra chevron SI tiene datos; solo muestra + si NO tiene datos
        $mostrar_add     = !$tiene_datos || $es_snack;
        $mostrar_chevron = $tiene_datos;
        $header_clickable = $tiene_datos ? 'clickable' : '';
        $onclick_hdr = $tiene_datos ? "onclick=\"toggleMeal('{$safe_id}')\"" : '';
    ?>
    <div class="meal-card">
        <div class="meal-header <?= $header_clickable ?>"
             id="hdr-<?= $safe_id ?>"
             <?= $onclick_hdr ?>
             aria-expanded="<?= $tiene_datos ? 'true' : 'false' ?>">

            <div class="meal-icon-bubble" style="background:<?= $cfg['bg'] ?>;">
                <?= $cfg['icon'] ?>
            </div>

            <div class="meal-text">
                <div class="meal-name"><?= $tipo ?><?= $tiene_datos ? ' ✓' : '' ?></div>
                <?php if ($tiene_datos): ?>
                    <div class="meal-kcal"><?= $total_tipo ?> kcal</div>
                <?php else: ?>
                    <div class="meal-pending-label">Sin registros</div>
                <?php endif; ?>
            </div>

            <div class="meal-actions">
                <?php if ($mostrar_add): ?>
                <button class="btn-add-item" title="Agregar alimento"
                        onclick="event.stopPropagation(); abrirModal('<?= $tipo ?>')">+</button>
                <?php endif; ?>

                <?php if ($mostrar_chevron): ?>
                <svg id="chevron-<?= $safe_id ?>"
                     class="meal-chevron up"
                     width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($tiene_datos): ?>
        <div class="meal-content open" id="meal-<?= $safe_id ?>">
            <div class="meal-divider"></div>
            <?php foreach ($alimentos as $alim): ?>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name"><?= htmlspecialchars($alim['nombre'] ?? '—') ?></span>
                <span class="meal-item-meta"><?= round($alim['calorias']) ?> kcal</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php include 'includes/footer.php'; ?>

</div><!-- /mobile-container -->

<!-- ── MODAL REGISTRO MANUAL ── -->
<div class="modal-overlay" id="modal-overlay">
    <div class="modal-sheet" onclick="event.stopPropagation()">

        <!-- ══ VISTA 1: Constructor de lista ══ -->
        <div class="modal-view active" id="vista-lista">
            <div class="modal-title">
                <span id="modal-titulo">Agregar a Desayuno</span>
                <button class="modal-close" id="btn-modal-close">×</button>
            </div>

            <!-- Fila labels -->
            <div class="add-row-labels">
                <span>Buscar alimento</span>
                <span>Cantidad (g)</span>
                <span></span>
            </div>

            <!-- Fila de entrada -->
            <div class="add-row">
                <div class="search-wrap" id="search-wrap">
                    <input type="text" id="input-busqueda"
                           placeholder="Ej: Arroz, Manzana…"
                           autocomplete="off" spellcheck="false">
                    <div class="ac-dropdown" id="ac-dropdown"></div>
                </div>
                <input type="number" id="input-cantidad" class="qty-input"
                       placeholder="—" min="1" max="5000" step="1">
                <button type="button" class="btn-add-alim" id="btn-add-alim"
                        disabled title="Selecciona un alimento y escribe la cantidad">+</button>
            </div>

            <!-- Lista de ítems -->
            <div class="items-list" id="items-list">
                <div class="items-list-empty" id="items-empty">Tu lista está vacía. Busca un alimento arriba.</div>
            </div>

            <!-- Botón registrar -->
            <button class="btn-registrar" id="btn-registrar" disabled>
                Revisar registro
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </button>
        </div>

        <!-- ══ VISTA 2: Confirmación ══ -->
        <div class="modal-view" id="vista-preview">
            <div class="modal-title">
                <span>Confirmar registro</span>
                <button class="modal-close" id="btn-modal-close-2">×</button>
            </div>
            <p class="preview-header" id="preview-header">Desayuno — Hoy</p>
            <div class="preview-table-wrap">
                <table class="preview-table" id="preview-table">
                    <thead>
                        <tr>
                            <th>Alimento</th>
                            <th>g</th>
                            <th>kcal</th>
                            <th>P</th>
                            <th>C</th>
                            <th>G</th>
                        </tr>
                    </thead>
                    <tbody id="preview-tbody"></tbody>
                </table>
            </div>
            <div class="preview-actions">
                <button class="btn-editar" id="btn-editar">← Editar</button>
                <button class="btn-confirmar" id="btn-confirmar">✓ Confirmar</button>
            </div>
        </div>

    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ─── CONSTANTES DESDE PHP ─────────────────────────────────────────
const FECHA_HOY  = '<?= $fecha_hoy ?>';
const FECHA_SEL  = '<?= $fecha_sel ?>';
const MESES      = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// ─── UTILIDADES DE FECHA ──────────────────────────────────────────
function fmtDate(d) {
    // Devuelve YYYY-MM-DD de un objeto Date, evitando problemas de zona horaria
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function addDays(dateStr, n) {
    // Suma n días a una fecha en formato YYYY-MM-DD
    const d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() + n);
    return fmtDate(d);
}

function sundayOf(dateStr) {
    // Devuelve el domingo de la semana que contiene dateStr
    const d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() - d.getDay()); // retrocede al domingo
    return fmtDate(d);
}

// ─── RENDERIZADO DE UNA SEMANA ────────────────────────────────────
function renderWeekSlide(el, domingoStr) {
    let html = '';
    for (let i = 0; i < 7; i++) {
        const fechaDia = addDays(domingoStr, i);
        const esFut   = fechaDia > FECHA_HOY;
        const esHoy   = fechaDia === FECHA_HOY;
        const esSel   = fechaDia === FECHA_SEL;
        const numDia  = parseInt(fechaDia.split('-')[2], 10);

        let cls = 'cal-day';
        if (esFut)        cls += ' future';
        if (esHoy && !esSel) cls += ' today';
        if (esSel)        cls += ' active';

        const onclick = esFut ? '' : `onclick="irAFecha('${fechaDia}')"`;
        html += `<div class="${cls}" ${onclick}>
                    <div class="cal-day-num">${numDia}</div>
                 </div>`;
    }
    el.innerHTML = html;
}

// ─── CALENDARIO SEMANAL con SWIPE ────────────────────────────────
const track     = document.getElementById('cal-week-track');
const slidePrev = document.getElementById('slide-prev');
const slideCurr = document.getElementById('slide-curr');
const slideNext = document.getElementById('slide-next');
const mesLabel  = document.getElementById('cal-mes-label');
const btnPrev   = document.getElementById('btn-prev-mes');

let semanaActual = sundayOf(FECHA_SEL); // domingo de la semana visible

function actualizarCalendario() {
    const dom_prev = addDays(semanaActual, -7);
    const dom_next = addDays(semanaActual,  7);

    renderWeekSlide(slidePrev, dom_prev);
    renderWeekSlide(slideCurr, semanaActual);
    renderWeekSlide(slideNext, dom_next);

    // Posicionar track en la slide central (sin animación)
    track.style.transition = 'none';
    track.style.transform  = 'translateX(-100%)';

    // Actualizar label del mes (según la fecha de jueves de la semana, para estabilidad)
    const jueves = addDays(semanaActual, 4);
    const [y, m] = jueves.split('-');
    mesLabel.textContent = MESES[parseInt(m, 10) - 1] + ' ' + y;
}

function irAFecha(fecha) {
    if (fecha > FECHA_HOY) return;
    window.location.href = 'diario.php?fecha=' + fecha;
}

// Swipe
let touchStartX = 0;
const wrapper = document.getElementById('cal-week-wrapper');

// ── Swipe táctil (móvil) ─────────────────────────────────────────
wrapper.addEventListener('touchstart', e => {
    touchStartX = e.touches[0].clientX;
}, { passive: true });

wrapper.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - touchStartX;
    navegarPorDelta(dx);
}, { passive: true });

// ── Swipe con trackpad de laptop (wheel horizontal) ───────────────
let wheelDebounce = null;
let wheelAcum     = 0;

wrapper.addEventListener('wheel', e => {
    // Solo reaccionar a scroll horizontal pronunciado
    if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) return;
    e.preventDefault();

    wheelAcum += e.deltaX;

    // Debounce: espera a que el gesto termine antes de navegar
    clearTimeout(wheelDebounce);
    wheelDebounce = setTimeout(() => {
        if (Math.abs(wheelAcum) > 60) {
            // deltaX positivo = deslizó a la izquierda (quiere avanzar)
            // deltaX negativo = deslizó a la derecha (quiere retroceder)
            navegarPorDelta(wheelAcum > 0 ? -80 : 80); // invierte para coincidir con touch
        }
        wheelAcum = 0;
    }, 120);
}, { passive: false });

// Función compartida por touch y wheel
function navegarPorDelta(dx) {
    if (Math.abs(dx) < 50) return;

    if (dx > 0) {
        // Retroceder → semana anterior
        const nuevaFecha = addDays(FECHA_SEL, -7);
        const clamped    = nuevaFecha > FECHA_HOY ? FECHA_HOY : nuevaFecha;
        track.style.transition = 'transform 0.3s ease';
        track.style.transform  = 'translateX(0%)';
        track.addEventListener('transitionend', function go() {
            track.removeEventListener('transitionend', go);
            irAFecha(clamped);
        });
    } else {
        // Avanzar → semana siguiente (solo si no es futura)
        const siguienteFecha = addDays(FECHA_SEL, 7);
        if (siguienteFecha <= FECHA_HOY) {
            track.style.transition = 'transform 0.3s ease';
            track.style.transform  = 'translateX(-200%)';
            track.addEventListener('transitionend', function go() {
                track.removeEventListener('transitionend', go);
                irAFecha(siguienteFecha);
            });
        } else {
            // Rebote visual — no hay semanas futuras
            track.style.transition = 'transform 0.2s ease';
            track.style.transform  = 'translateX(-115%)';
            setTimeout(() => { track.style.transform = 'translateX(-100%)'; }, 200);
        }
    }
}

// Botón mes anterior → ir al mismo día un mes atrás
btnPrev.addEventListener('click', () => {
    const d = new Date(FECHA_SEL + 'T12:00:00');
    d.setMonth(d.getMonth() - 1);
    let nueva = fmtDate(d);
    if (nueva > FECHA_HOY) nueva = FECHA_HOY;
    irAFecha(nueva);
});

// Inicializar
actualizarCalendario();

// ─── ACORDEÓN ────────────────────────────────────────────────────
function toggleMeal(id) {
    const content = document.getElementById('meal-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const header  = document.getElementById('hdr-' + id);
    if (!content) return;
    const isOpen = content.classList.toggle('open');
    if (chevron) chevron.classList.toggle('up', isOpen);
    if (header)  header.setAttribute('aria-expanded', isOpen);
}

// ══════════════════════════════════════════════════════════════════
// MODAL — Registro de alimentos en lista (2 pasos)
// ══════════════════════════════════════════════════════════════════

// ─── Estado ──────────────────────────────────────────────────────
let modalTipo  = '';
let modalFecha = '<?= $fecha_sel ?>';
let listaItems = [];       // [{nombre, id_alimento, gramos, cal_100, prot_100, c_100, g_100}]
let itemTemp   = null;     // resultado seleccionado del dropdown (pendiente de agregar)

// ─── Referencias DOM ─────────────────────────────────────────────
const overlay     = document.getElementById('modal-overlay');
const vistaLista  = document.getElementById('vista-lista');
const vistaPreview= document.getElementById('vista-preview');
const inputBusq   = document.getElementById('input-busqueda');
const inputQty    = document.getElementById('input-cantidad');
const btnAddAlim  = document.getElementById('btn-add-alim');
const itemsList   = document.getElementById('items-list');
const itemsEmpty  = document.getElementById('items-empty');
const btnReg      = document.getElementById('btn-registrar');
const acDrop      = document.getElementById('ac-dropdown');

// ─── Abrir / cerrar modal ─────────────────────────────────────────
function abrirModal(tipo) {
    modalTipo  = tipo;
    listaItems = [];
    itemTemp   = null;
    document.getElementById('modal-titulo').textContent = 'Agregar a ' + tipo;
    mostrarVista('lista');
    renderLista();
    overlay.classList.add('active');
    requestAnimationFrame(() => inputBusq.focus());
}

function cerrarModal() {
    overlay.classList.remove('active');
    inputBusq.value  = '';
    inputQty.value   = '';
    acDrop.classList.remove('open');
    acDrop.innerHTML = '';
    itemTemp = null;
    actualizarBtnAdd();
}

overlay.addEventListener('click', e => { if (e.target === overlay) cerrarModal(); });
document.getElementById('btn-modal-close').addEventListener('click',  cerrarModal);
document.getElementById('btn-modal-close-2').addEventListener('click', cerrarModal);

// ─── Cambio de vista ──────────────────────────────────────────────
function mostrarVista(cual) {
    vistaLista.classList.toggle('active',   cual === 'lista');
    vistaPreview.classList.toggle('active', cual === 'preview');
}

// ─── Render de la lista de ítems ─────────────────────────────────
function renderLista() {
    const tieneItems = listaItems.length > 0;
    itemsEmpty.style.display = tieneItems ? 'none' : 'block';
    btnReg.disabled = !tieneItems;

    // Limpiar ítems previos (conservar el mensaje vacío)
    Array.from(itemsList.querySelectorAll('.item-row')).forEach(el => el.remove());

    listaItems.forEach((it, idx) => {
        const row = document.createElement('div');
        row.className = 'item-row';
        row.innerHTML = `
            <div class="item-dot"></div>
            <div class="item-name" title="${escH(it.nombre)}">${escH(it.nombre)}</div>
            <div class="item-qty">${it.gramos} g</div>
            <button class="btn-remove-item" title="Eliminar" data-idx="${idx}">✕</button>
        `;
        itemsList.appendChild(row);
    });

    // Delegación de eventos para eliminar
    itemsList.querySelectorAll('.btn-remove-item').forEach(btn => {
        btn.addEventListener('click', () => {
            listaItems.splice(parseInt(btn.dataset.idx), 1);
            renderLista();
        });
    });
}

// ─── Autocompletado ───────────────────────────────────────────────
let acTimer = null;

function actualizarBtnAdd() {
    const qty = parseFloat(inputQty.value);
    btnAddAlim.disabled = !(itemTemp && qty > 0);
}

inputBusq.addEventListener('input', () => {
    // Al escribir de nuevo, reseteamos la selección temporal
    itemTemp = null;
    actualizarBtnAdd();
    clearTimeout(acTimer);
    const q = inputBusq.value.trim();
    if (q.length < 2) { acDrop.classList.remove('open'); return; }
    acDrop.innerHTML = '<div class="ac-status">🔍 Buscando…</div>';
    acDrop.classList.add('open');
    acTimer = setTimeout(() => buscarAlimento(q), 450);
});

inputQty.addEventListener('input', actualizarBtnAdd);

async function buscarAlimento(query) {
    try {
        const resp = await fetch(
            `../controllers/api_alimentos.php?query=${encodeURIComponent(query)}`,
            { credentials: 'same-origin' }
        );
        const json = await resp.json();

        if (json.status !== 'success' || !json.data) {
            acDrop.innerHTML = `<div class="ac-status">Sin resultados para "${escH(query)}".<br>
                <a href="chat_ia.php">Prueba en tu chat NutrIAssist →</a></div>`;
            return;
        }

        const d    = json.data;
        const kcal = Math.round(parseFloat(d.calorias_por_100g) || 0);
        const prot = parseFloat(d.proteina_por_100g || 0).toFixed(1);
        const carb = parseFloat(d.carbs_por_100g    || 0).toFixed(1);
        const gras = parseFloat(d.grasas_por_100g   || 0).toFixed(1);
        const badge = json.fuente === 'mysql_local'
            ? '<span class="ac-badge local">Local</span>'
            : '<span class="ac-badge usda">USDA</span>';
        const displayName = d.nombre ?? json.nombre_canonico ?? query;

        acDrop.innerHTML = `
            <div class="ac-item" id="ac-result" tabindex="0" role="option">
                <span class="ac-item-icon">🥗</span>
                <div class="ac-item-info">
                    <div class="ac-item-name">${escH(displayName)}</div>
                    <div class="ac-item-sub">${kcal} kcal · P ${prot}g · C ${carb}g · G ${gras}g <small>(por 100g)</small></div>
                </div>
                ${badge}
            </div>
        `;

        const acResult = document.getElementById('ac-result');
        const seleccionar = () => {
            itemTemp = {
                nombre:    displayName,
                id_alimento: d.id_alimento ?? null,
                cal_100:   parseFloat(d.calorias_por_100g) || 0,
                prot_100:  parseFloat(d.proteina_por_100g) || 0,
                c_100:     parseFloat(d.carbs_por_100g)    || 0,
                g_100:     parseFloat(d.grasas_por_100g)   || 0,
            };
            inputBusq.value = displayName;
            acDrop.classList.remove('open');
            actualizarBtnAdd();
            inputQty.focus();
        };
        acResult.addEventListener('click', seleccionar);
        acResult.addEventListener('keydown', ev => {
            if (ev.key === 'Enter' || ev.key === ' ') seleccionar();
        });

    } catch {
        acDrop.innerHTML = '<div class="ac-status">Error de conexión. Inténtalo de nuevo.</div>';
    }
}

// Cerrar dropdown al clic fuera
document.addEventListener('click', e => {
    if (!document.getElementById('search-wrap').contains(e.target)) {
        acDrop.classList.remove('open');
    }
});

// ─── Agregar ítem a la lista ──────────────────────────────────────
btnAddAlim.addEventListener('click', () => {
    if (!itemTemp) return;
    const gramos = parseFloat(inputQty.value);
    if (!(gramos > 0)) { mostrarToast('Ingresa una cantidad válida'); return; }

    listaItems.push({
        nombre:      itemTemp.nombre,
        id_alimento: itemTemp.id_alimento,
        gramos:      gramos,
        cal_100:     itemTemp.cal_100,
        prot_100:    itemTemp.prot_100,
        c_100:       itemTemp.c_100,
        g_100:       itemTemp.g_100,
    });

    // Reset campo de entrada
    inputBusq.value  = '';
    inputQty.value   = '';
    itemTemp         = null;
    acDrop.classList.remove('open');
    acDrop.innerHTML = '';
    actualizarBtnAdd();
    renderLista();
    inputBusq.focus();
});

// Enter en cantidad → agregar
inputQty.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); btnAddAlim.click(); }
});

// ─── Paso 1 → Paso 2: construir preview ──────────────────────────
btnReg.addEventListener('click', () => {
    if (listaItems.length === 0) return;

    const tbody = document.getElementById('preview-tbody');
    tbody.innerHTML = '';

    let totCal = 0, totProt = 0, totC = 0, totG = 0;

    listaItems.forEach(it => {
        const f    = it.gramos / 100;
        const cal  = Math.round(it.cal_100  * f);
        const prot = +(it.prot_100 * f).toFixed(1);
        const c    = +(it.c_100   * f).toFixed(1);
        const g    = +(it.g_100   * f).toFixed(1);

        // Guardar macros calculados en el ítem para el POST
        it.calorias_ia = cal;
        it.proteina_ia = prot;
        it.carbs_ia    = c;
        it.grasas_ia   = g;

        totCal  += cal;
        totProt += prot;
        totC    += c;
        totG    += g;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="td-name" title="${escH(it.nombre)}">${escH(it.nombre)}</td>
            <td class="td-qty">${it.gramos}</td>
            <td>${cal}</td>
            <td>${prot}</td>
            <td>${c}</td>
            <td>${g}</td>
        `;
        tbody.appendChild(tr);
    });

    // Fila de totales
    const trTotal = document.createElement('tr');
    trTotal.className = 'total-row';
    trTotal.innerHTML = `
        <td class="td-name">TOTAL</td>
        <td>—</td>
        <td>${totCal}</td>
        <td>${totProt.toFixed(1)}</td>
        <td>${totC.toFixed(1)}</td>
        <td>${totG.toFixed(1)}</td>
    `;
    tbody.appendChild(trTotal);

    // Actualizar header de la vista previa
    const hoyLabel = modalFecha === FECHA_HOY ? 'Hoy' : modalFecha;
    document.getElementById('preview-header').textContent =
        `${modalTipo} — ${hoyLabel}`;

    mostrarVista('preview');
});

// ─── Paso 2 → Paso 1: editar ─────────────────────────────────────
document.getElementById('btn-editar').addEventListener('click', () => {
    mostrarVista('lista');
});

// ─── Confirmar → POST ────────────────────────────────────────────
document.getElementById('btn-confirmar').addEventListener('click', async function() {
    this.disabled    = true;
    this.textContent = 'Guardando…';

    const payload = {
        tipo_comida: modalTipo,
        fecha:       modalFecha,
        items:       listaItems.map(it => ({
            nombre:      it.nombre,
            id_alimento: it.id_alimento,
            gramos:      it.gramos,
            calorias_ia: it.calorias_ia,
            proteina_ia: it.proteina_ia,
            carbs_ia:    it.carbs_ia,
            grasas_ia:   it.grasas_ia,
        })),
    };

    try {
        const resp = await fetch('../controllers/guardar_comida_lista.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
            credentials: 'same-origin',
        });
        const json = await resp.json();

        if (json.status === 'success') {
            cerrarModal();
            mostrarToast('✓ Comida registrada');
            setTimeout(() => location.reload(), 900);
        } else {
            mostrarToast('Error: ' + (json.message || 'Inténtalo de nuevo'));
            this.disabled    = false;
            this.textContent = '✓ Confirmar';
        }
    } catch {
        mostrarToast('Error de conexión');
        this.disabled    = false;
        this.textContent = '✓ Confirmar';
    }
});

// ─── Utilidades ───────────────────────────────────────────────────
function escH(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function mostrarToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}
</script>

</body>
</html>
