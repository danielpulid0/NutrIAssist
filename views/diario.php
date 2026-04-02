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
            background: rgba(0,0,0,0.45); z-index: 1000; /* por encima del footer */
            align-items: flex-end; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-sheet {
            background: #fff; border-radius: 20px 20px 0 0;
            width: 100%; max-width: 480px;
            padding: 1.5rem 1.25rem 2rem;
            animation: slideUp 0.28s ease;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .modal-title {
            font-size: 1.05rem; font-weight: 700; color: var(--color-text-dark);
            margin-bottom: 1.25rem; display: flex;
            justify-content: space-between; align-items: center;
        }
        .modal-close {
            background: none; border: none; font-size: 1.5rem;
            cursor: pointer; color: var(--color-text-gray); line-height: 1;
        }
        .form-group { margin-bottom: 0.85rem; }
        .form-group label {
            display: block; font-size: 0.75rem; font-weight: 600;
            color: var(--color-text-gray); margin-bottom: 0.3rem;
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        .form-group input {
            width: 100%; padding: 0.7rem 0.9rem;
            border: 1px solid var(--color-border); border-radius: 10px;
            font-size: 0.95rem; font-family: 'Inter', sans-serif;
            color: var(--color-text-dark); background: #fff; outline: none;
            transition: border-color 0.15s; box-sizing: border-box;
        }
        .form-group input:focus { border-color: var(--color-malachite); }
        .macros-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.6rem; }
        .btn-guardar-manual {
            width: 100%; background-color: var(--color-malachite);
            color: #fff; border: none; border-radius: 99px;
            padding: 0.9rem; font-size: 0.95rem; font-weight: 700;
            cursor: pointer; font-family: 'Inter', sans-serif;
            transition: background 0.15s; margin-top: 0.5rem;
        }
        .btn-guardar-manual:hover { background: #0db844; }
        .btn-guardar-manual:disabled { background: #9CA3AF; cursor: not-allowed; }

        /* ── TOAST ── */
        .toast {
            position: fixed; bottom: 100px; left: 50%;
            transform: translateX(-50%);
            background: #1F2937; color: #fff;
            padding: 0.65rem 1.2rem; border-radius: 99px;
            font-size: 0.85rem; font-weight: 500; z-index: 300;
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
        <div class="modal-title">
            <span id="modal-titulo">Agregar Alimento</span>
            <button class="modal-close" id="btn-modal-close">×</button>
        </div>
        <form id="form-manual" onsubmit="guardarManual(event)">
            <input type="hidden" id="input-tipo" name="tipo_comida">
            <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha_sel) ?>">

            <div class="form-group">
                <label for="input-nombre">Nombre del alimento *</label>
                <input type="text" id="input-nombre" name="nombre"
                       placeholder="Ej: Ensalada César" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="input-calorias">Calorías (kcal) *</label>
                <input type="number" id="input-calorias" name="calorias"
                       placeholder="0" min="0" max="9999" required>
            </div>
            <div class="macros-row">
                <div class="form-group">
                    <label for="input-proteina">Proteína (g)</label>
                    <input type="number" id="input-proteina" name="proteina"
                           placeholder="0" min="0" step="0.1">
                </div>
                <div class="form-group">
                    <label for="input-carbs">Carbs (g)</label>
                    <input type="number" id="input-carbs" name="carbs"
                           placeholder="0" min="0" step="0.1">
                </div>
                <div class="form-group">
                    <label for="input-grasas">Grasas (g)</label>
                    <input type="number" id="input-grasas" name="grasas"
                           placeholder="0" min="0" step="0.1">
                </div>
            </div>
            <button type="submit" class="btn-guardar-manual" id="btn-guardar">
                Guardar
            </button>
        </form>
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

// ─── MODAL ───────────────────────────────────────────────────────
function abrirModal(tipo) {
    document.getElementById('modal-titulo').textContent = 'Agregar a ' + tipo;
    document.getElementById('input-tipo').value = tipo;
    document.getElementById('form-manual').reset();
    document.getElementById('input-tipo').value = tipo; // reset borra el hidden
    document.getElementById('modal-overlay').classList.add('active');
    requestAnimationFrame(() => document.getElementById('input-nombre').focus());
}

document.getElementById('modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});
document.getElementById('btn-modal-close').addEventListener('click', () => {
    document.getElementById('modal-overlay').classList.remove('active');
});

async function guardarManual(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando…';

    const fd = new FormData(document.getElementById('form-manual'));
    const payload = {
        tipo_comida: fd.get('tipo_comida'),
        fecha:       fd.get('fecha'),
        alimento:    fd.get('nombre'),
        calorias:    parseFloat(fd.get('calorias'))  || 0,
        proteina:    parseFloat(fd.get('proteina'))  || 0,
        carbs:       parseFloat(fd.get('carbs'))     || 0,
        grasas:      parseFloat(fd.get('grasas'))    || 0,
    };

    try {
        const resp = await fetch('../controllers/guardar_comida_manual.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        });
        const json = await resp.json();
        if (json.status === 'success') {
            document.getElementById('modal-overlay').classList.remove('active');
            mostrarToast('✓ Guardado correctamente');
            setTimeout(() => location.reload(), 900);
        } else {
            mostrarToast('Error: ' + (json.message || 'Inténtalo de nuevo'));
            btn.disabled = false;
            btn.textContent = 'Guardar';
        }
    } catch {
        mostrarToast('Error de conexión');
        btn.disabled = false;
        btn.textContent = 'Guardar';
    }
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
