<?php
require_once '../controllers/recetas_controller.php';

$page_title = 'NutrIAssist – Recetas';
$extra_css  = '../assets/css/recetas.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">

    <h1 class="page-title-center">
        Recetas
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
               value="<?= htmlspecialchars($termino_busqueda) ?>"
               autocomplete="off">
    </form>

    <!-- Chips de filtro -->
    <div class="chips-container">
        <a href="recetas.php<?= $termino_busqueda ? '?q='.urlencode($termino_busqueda) : '' ?>"
           class="chip <?= $filtro_tag === '' ? 'active' : '' ?>">Todos</a>
        <?php foreach ($todos_tags as $tag_actual): ?>
        <a href="recetas.php?tag=<?= urlencode($tag_actual) ?><?= $termino_busqueda ? '&q='.urlencode($termino_busqueda) : '' ?>"
           class="chip <?= $filtro_tag === $tag_actual ? 'active' : '' ?>">
            <?= htmlspecialchars($tag_actual) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Grid de recetas -->
    <div class="recetas-grid">
        <?php if (empty($recetas)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20M6 12V7a6 6 0 0 1 12 0v5"/><path d="M4 12c0 4.418 3.582 8 8 8s8-3.582 8-8"/></svg></div>
            <p>No se encontraron recetas.</p>
        </div>
        <?php endif; ?>

        <?php foreach ($recetas as $receta_card): ?>
        <a href="receta_detalle.php?id=<?= $receta_card['id_receta'] ?>" class="receta-card">

            <!-- Imagen -->
            <div class="receta-img-wrapper">
                <?php if (!empty($receta_card['imagen_url'])): ?>
                <img src="<?= (strpos($receta_card['imagen_url'], 'http') === 0) ? htmlspecialchars($receta_card['imagen_url']) : '../assets/img/recetas/' . htmlspecialchars($receta_card['imagen_url']) ?>"
                     alt="<?= htmlspecialchars($receta_card['titulo']) ?>"
                     onerror="this.style.display='none'; this.parentNode.innerHTML='<svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23ccc\' stroke-width=\'1.5\' class=\'img-error-svg\'><path d=\'M2 12h20M6 12V7a6 6 0 0 1 12 0v5\'/><path d=\'M4 12c0 4.418 3.582 8 8 8s8-3.582 8-8\'/></svg>';">
                <?php else: ?>
                <div class="receta-img-placeholder"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20M6 12V7a6 6 0 0 1 12 0v5"/><path d="M4 12c0 4.418 3.582 8 8 8s8-3.582 8-8"/></svg></div>
                <?php endif; ?>
            </div>

            <div class="receta-info">
                <div class="receta-title"><?= htmlspecialchars($receta_card['titulo']) ?></div>

                <div class="receta-meta">
                    <?php if ($receta_card['tiempo_prep_min']): ?>
                    <span class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?= $receta_card['tiempo_prep_min'] ?> min
                    </span>
                    <?php endif; ?>
                    <span class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                            <circle cx="12" cy="12" r="2"/>
                        </svg>
                        <?= htmlspecialchars($receta_card['costo_txt']) ?>
                    </span>
                </div>

                <div class="receta-footer">
                    <span class="receta-kcal"><?= $receta_card['calorias_totales'] ?> kcal</span>
                    <?php if ($receta_card['tag_visible']): ?>
                    <span class="receta-tag"
                          style="background:<?= $receta_card['color_tag']['bg'] ?>; color:<?= $receta_card['color_tag']['fg'] ?>;">
                        <?= htmlspecialchars($receta_card['tag_visible']) ?>
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
<link rel="stylesheet" href="../assets/css/tutorial.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/intro.min.js"></script>

<script>
function startTutorial() {
    var intro = introJs();
    intro.setOptions({
        steps: [
            {
                title: 'Explora Recetas',
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
                title: '¡Todo listo!',
                intro: 'Has completado el recorrido. Recuerda que puedes volver a ver esta guía desde tu perfil. ¡A disfrutar!'
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