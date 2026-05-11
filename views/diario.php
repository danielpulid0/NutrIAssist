<?php
require_once '../controllers/diario_controller.php';

$page_title = 'NutrIAssist – Diario';
$extra_css = '../assets/css/diario.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">

    <h1 class="page-title-center">
        Diario de Comidas
    </h1>

    <!-- ── CALENDARIO SEMANAL ── -->
    <div class="calendar-card">
        <div class="cal-header">
            <button class="cal-prev-mes" id="btn-prev-mes" aria-label="Mes anterior">&#8249;</button>
            <span class="cal-mes-label" id="cal-mes-label"><?= $label_mes ?></span>
        </div>

        <!-- Etiquetas de días (fijas) -->
        <div class="cal-day-labels">
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
        'Desayuno' => [
            'bg' => '#FFF3E0',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/></svg>'
        ],
        'Comida' => [
            'bg' => '#F0FFF4',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="#11CF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>'
        ],
        'Cena' => [
            'bg' => '#EDE9FE',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>'
        ],
        'Snack' => [
            'bg' => '#FFF0F6',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="#EC4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="9" cy="10" r="1" fill="#EC4899"/><circle cx="14" cy="9" r="1" fill="#EC4899"/><circle cx="10" cy="14" r="1" fill="#EC4899"/><circle cx="15" cy="14" r="1" fill="#EC4899"/></svg>'
        ],
    ];

    foreach ($tipos_orden as $tipo):
        $alimentos = $comidas_grupos[$tipo] ?? [];
        $tiene_datos = count($alimentos) > 0;
        $total_tipo = round(array_sum(array_column($alimentos, 'calorias')));
        $cfg = $meal_cfg[$tipo];
        $safe_id = strtolower($tipo);
        $es_snack = ($tipo === 'Snack');

        // Lógica de botones (la regla de negocio central):
        // - Snack: siempre muestra +; también muestra chevron si tiene datos
        // - Resto: solo muestra chevron SI tiene datos; solo muestra + si NO tiene datos
        $mostrar_add = !$tiene_datos || $es_snack;
        $mostrar_chevron = $tiene_datos;
        $header_clickable = $tiene_datos ? 'clickable' : '';
        $onclick_hdr = $tiene_datos ? "onclick=\"toggleMeal('{$safe_id}')\"" : '';
        ?>
        <div class="meal-card">
            <div class="meal-header <?= $header_clickable ?>" id="hdr-<?= $safe_id ?>" <?= $onclick_hdr ?>
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
                        <svg id="chevron-<?= $safe_id ?>" class="meal-chevron up" width="20" height="20" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($tiene_datos): ?>
                <div class="meal-content open" id="meal-<?= $safe_id ?>">
                    <div class="meal-divider"></div>
                    <?php foreach ($alimentos as $alim): ?>
                        <div class="meal-item" id="item-consumo-<?= $alim['id_consumo'] ?>">
                            <div class="meal-item-dot"></div>
                            <span class="meal-item-name"><?= htmlspecialchars($alim['nombre'] ?? '—') ?></span>
                            <span class="meal-item-meta"><?= round($alim['calorias']) ?> kcal</span>
                            <button class="btn-delete-item" onclick="eliminarRegistro(<?= $alim['id_consumo'] ?>)" title="Eliminar registro" style="background:none; border:none; color:var(--color-text-gray); cursor:pointer; padding: 4px; display:flex; align-items:center; margin-left: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
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
                    <input type="text" id="input-busqueda" placeholder="Ej: Arroz, Manzana…" autocomplete="off"
                        spellcheck="false">
                    <div class="ac-dropdown" id="ac-dropdown"></div>
                </div>
                <input type="number" id="input-cantidad" class="qty-input" placeholder="—" min="1" max="5000" step="1">
                <button type="button" class="btn-add-alim" id="btn-add-alim" disabled
                    title="Selecciona un alimento y escribe la cantidad">+</button>
            </div>

            <!-- Lista de ítems -->
            <div class="items-list" id="items-list">
                <div class="items-list-empty" id="items-empty">Tu lista está vacía. Busca un alimento arriba.</div>
            </div>

            <!-- Botón registrar -->
            <button class="btn-registrar" id="btn-registrar" disabled>
                Revisar registro
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6" />
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
                <button class="btn-editar" id="btn-editar">Editar</button>
                <button class="btn-confirmar" id="btn-confirmar">Confirmar</button>
            </div>
        </div>

    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<!-- Intro.js CSS & JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/introjs.min.css">
<link rel="stylesheet" href="../assets/css/tutorial.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/intro.min.js"></script>

<script>
function startTutorial(isMultiPage = true) {
    var intro = introJs();
    intro.setOptions({
        steps: [
            {
                title: 'Tu Diario Personal',
                intro: 'Aquí es donde sucede la magia. Registrar tus comidas es el hábito #1 para alcanzar tus metas.'
            },
            {
                element: document.querySelector('.calendar-card'),
                title: 'Calendario Semanal',
                intro: 'Puedes navegar entre los días de la semana para revisar qué comiste o planificar el futuro.'
            },
            {
                element: document.querySelector('.cals-card'),
                title: 'Resumen del Día',
                intro: 'Aquí ves el total acumulado de hoy. ¡Mantén esa llama encendida!'
            },
            {
                element: document.querySelector('.meal-card'),
                title: 'Registrar Comidas',
                intro: 'Pulsa en el botón "+" de cualquier sección (Desayuno, Comida, etc.) para buscar y agregar alimentos.',
                position: 'bottom'
            },
            {
                element: document.querySelectorAll('.nav-item')[2],
                title: '¡Casi terminamos!',
                intro: isMultiPage ? 'Ahora, vamos a echar un vistazo a la biblioteca de recetas. Pulsa aquí para terminar la guía.' : 'Desde aquí puedes ir a la sección de recetas para descubrir nuevas ideas saludables.',
                position: 'top'
            }
        ],
        nextLabel: 'Siguiente',
        prevLabel: 'Atrás',
        doneLabel: isMultiPage ? 'Ir a Recetas' : '¡Entendido!',
        showSkipButton: true,
        skipLabel: 'Saltar',
        overlayOpacity: 0.8
    });

    intro.oncomplete(function() {
        if (isMultiPage) {
            window.location.href = 'recetas.php?tutorial=1';
        }
    });

    intro.start();
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tutorial') === '1') {
        setTimeout(() => { startTutorial(true); }, 800);
    }
});
</script>

<script>
    // Variables globales para el script modular
    window.NutriConfig = {
        FECHA_HOY: '<?= $fecha_hoy ?>',
        FECHA_SEL: '<?= $fecha_sel ?>',
        MESES: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
    };
</script>
<script src="../assets/js/diario.js"></script>

</body>

</html>