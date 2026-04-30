<?php
require_once '../controllers/recetas_controller.php';

$page_title = 'NutrIAssist – Recetas';
$extra_css  = '../assets/css/recetas.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">

    <h1 class="page-title-center">
        Recetas
        <button onclick="startTutorial()" class="tutorial-trigger" title="Ver guía de esta página" style="display: inline-flex; vertical-align: middle; margin-left: 8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 1 7 7c0 2.38-1.19 4.47-3 5.74V17a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2v-2.26C6.19 13.47 5 11.38 5 9a7 7 0 0 1 7-7z"/></svg>
        </button>
    </h1>

    <!-- Búsqueda -->
    <form method="GET" action="" class="search-wrapper">
        <span class="search-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </span>
        <input type="text" name="q" class="search-bar"
               placeholder="Buscar ..."
               value="<?= htmlspecialchars($q) ?>"
               autocomplete="off">
    </form>

    <!-- Chips de filtro -->
    <div class="chips-container">
        <a href="recetas.php<?= $q ? '?q='.urlencode($q) : '' ?>"
           class="chip <?= $filtro_tag === '' ? 'active' : '' ?>">Todos</a>
        <?php foreach ($todos_tags as $t): ?>
        <a href="recetas.php?tag=<?= urlencode($t) ?><?= $q ? '&q='.urlencode($q) : '' ?>"
           class="chip <?= $filtro_tag === $t ? 'active' : '' ?>">
            <?= htmlspecialchars($t) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Grid de recetas -->
    <div class="recetas-grid">
        <?php if (empty($recetas)): ?>
        <div class="empty-state">
            <div style="opacity:0.4;margin-bottom:0.5rem"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20M6 12V7a6 6 0 0 1 12 0v5"/><path d="M4 12c0 4.418 3.582 8 8 8s8-3.582 8-8"/></svg></div>
            <p>No se encontraron recetas.</p>
        </div>
        <?php endif; ?>

        <?php foreach ($recetas as $r): ?>
        <a href="receta_detalle.php?id=<?= $r['id_receta'] ?>" class="receta-card">

            <!-- Imagen -->
            <div class="receta-img-wrapper">
                <?php if (!empty($r['imagen_url'])): ?>
                <img src="<?= (strpos($r['imagen_url'], 'http') === 0) ? htmlspecialchars($r['imagen_url']) : '../assets/img/recetas/' . htmlspecialchars($r['imagen_url']) ?>"
                     alt="<?= htmlspecialchars($r['titulo']) ?>"
                     onerror="this.style.display='none'; this.parentNode.innerHTML='<svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23ccc\' stroke-width=\'1.5\' style=\'width:48px;height:48px\'><path d=\'M2 12h20M6 12V7a6 6 0 0 1 12 0v5\'/><path d=\'M4 12c0 4.418 3.582 8 8 8s8-3.582 8-8\'/></svg>';">
                <?php else: ?>
                <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;opacity:0.3"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px"><path d="M2 12h20M6 12V7a6 6 0 0 1 12 0v5"/><path d="M4 12c0 4.418 3.582 8 8 8s8-3.582 8-8"/></svg></div>
                <?php endif; ?>
            </div>

            <div class="receta-info">
                <div class="receta-title"><?= htmlspecialchars($r['titulo']) ?></div>

                <div class="receta-meta">
                    <?php if ($r['tiempo_prep_min']): ?>
                    <span class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?= $r['tiempo_prep_min'] ?> min
                    </span>
                    <?php endif; ?>
                    <span class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                            <circle cx="12" cy="12" r="2"/>
                        </svg>
                        <?= htmlspecialchars($r['costo_txt']) ?>
                    </span>
                </div>

                <div class="receta-footer">
                    <span class="receta-kcal"><?= $r['calorias_totales'] ?> kcal</span>
                    <?php if ($r['tag_visible']): ?>
                    <span class="receta-tag"
                          style="background:<?= $r['color_tag']['bg'] ?>; color:<?= $r['color_tag']['fg'] ?>;">
                        <?= htmlspecialchars($r['tag_visible']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

</div>

<!-- Intro.js CSS & JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/introjs.min.css">
<style>
    /* Tutorial styling (reused for consistency) */
    .introjs-tooltip {
        background-color: var(--color-bg-card, #ffffff) !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15) !important;
        color: var(--color-text) !important;
        font-family: 'Inter', sans-serif !important;
        max-width: 90vw !important;
        min-width: 320px !important;
        box-sizing: border-box !important;
        padding: 10px !important;
    }
    .introjs-tooltiptext {
        font-size: 15px !important;
        line-height: 1.6 !important;
        color: #1e293b !important;
        padding-bottom: 12px !important;
    }
    .introjs-tooltiptitle {
        font-size: 20px !important;
        font-weight: 800 !important;
        color: var(--color-text) !important;
        margin-bottom: 10px !important;
        padding-right: 50px !important;
        line-height: 1.2 !important;
    }
    .introjs-button {
        border-radius: 10px !important;
        font-weight: 700 !important;
        padding: 10px 20px !important;
    }
    .introjs-nextbutton, .introjs-donebutton {
        background: var(--color-malachite) !important;
        color: white !important;
    }
    .introjs-skipbutton {
        position: absolute !important;
        top: 15px !important;
        right: 15px !important;
        color: #94a3b8 !important;
    }
    body.dark-mode .introjs-tooltip { background-color: #1e293b !important; color: #f8fafc !important; }
    body.dark-mode .introjs-tooltiptext { color: #cbd5e1 !important; }
    .tutorial-trigger {
        background: none;
        border: none;
        padding: 0;
        color: var(--color-malachite);
        cursor: pointer;
        display: flex;
        align-items: center;
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    .tutorial-trigger:hover { opacity: 1; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/intro.min.js"></script>

<script>
function startTutorial() {
    var intro = introJs();
    intro.setOptions({
        steps: [
            {
                title: 'Explora Recetas 🍳',
                intro: '¿Cansado de comer siempre lo mismo? Aquí tienes cientos de opciones saludables que encajan en tu plan.'
            },
            {
                element: document.querySelector('.search-wrapper'),
                title: 'Buscador Inteligente',
                intro: 'Busca ingredientes o nombres de platos específicos para encontrar exactamente lo que te apetece.'
            },
            {
                element: document.querySelector('.chips-container'),
                title: 'Categorías Rápidas',
                intro: 'Filtra por tipo de dieta, tiempo o ingredientes clave con un solo toque.'
            },
            {
                element: document.querySelector('.recetas-grid'),
                title: 'Tarjetas de Receta',
                intro: 'Cada tarjeta te muestra el tiempo, el costo y las calorías totales. ¡Haz clic en una para ver el paso a paso!'
            },
            {
                title: '¡Todo listo! 🚀',
                intro: 'Has completado el recorrido. Recuerda que puedes volver a ver esta guía pulsando el icono de bombilla en la parte superior. ¡A disfrutar!'
            }
        ],
        nextLabel: 'Siguiente',
        prevLabel: 'Atrás',
        doneLabel: '¡Entendido!',
        showSkipButton: true,
        skipLabel: 'Saltar',
        overlayOpacity: 0.8
    });

    setTimeout(() => { intro.start(); }, 800);
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tutorial') === '1') {
        startTutorial();
    }
});
</script>

</body>
</html>