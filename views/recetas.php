<?php
require_once '../controllers/recetas_controller.php';

$page_title = 'NutrIAssist – Recetas';
$extra_css  = '../assets/css/recetas.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">

    <h1 class="page-title-center">Recetas</h1>

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
            <div style="font-size:2.5rem">🍽️</div>
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
                     onerror="this.style.display='none'; this.parentNode.innerHTML='🍲';">
                <?php else: ?>
                🍲
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
</body>
</html>