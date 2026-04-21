<?php
require_once '../controllers/receta_detalle_controller.php';

$page_title = 'NutrIAssist – ' . htmlspecialchars($receta['titulo']);
$extra_css  = '../assets/css/receta_detalle.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">

    <div class="detail-header">
        <a href="recetas.php" class="btn-back" aria-label="Volver">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <span class="header-title">Detalle de Receta</span>
        <div class="header-spacer"></div>
    </div>

    <div class="hero-section">
        <?php if (!empty($receta['imagen_url'])): ?>
        <img src="<?= (strpos($receta['imagen_url'], 'http') === 0) ? htmlspecialchars($receta['imagen_url']) : '../assets/img/recetas/' . htmlspecialchars($receta['imagen_url']) ?>"
             alt="<?= htmlspecialchars($receta['titulo']) ?>"
             onerror="this.style.display='none';">
        <?php else: ?>
        <span class="hero-emoji">🍲</span>
        <?php endif; ?>
        <div class="hero-overlay"></div>
        <h1 class="hero-title"><?= htmlspecialchars($receta['titulo']) ?></h1>
    </div>

    <div class="recipe-content">

        <div class="info-pills">
            <div class="info-pill">
                <div class="pill-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <span class="pill-label">Tiempo</span>
                <span class="pill-value"><?= htmlspecialchars($tiempo_txt) ?></span>
            </div>
            <div class="info-pill">
                <div class="pill-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <span class="pill-label">Costo</span>
                <span class="pill-value"><?= htmlspecialchars($costo) ?></span>
            </div>
            <div class="info-pill">
                <div class="pill-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="M12 8v4l3 3"/></svg></div>
                <span class="pill-label">Calorías</span>
                <span class="pill-value"><?= $receta['calorias_totales'] ?> kcal</span>
            </div>
        </div>

        <h2 class="section-title">Ingredientes</h2>
        <ul class="ingredient-list">
            <?php foreach ($ingredientes as $i => $ing): ?>
            <li class="ingredient-item">
                <div class="ingr-text">
                    <div class="ingr-name" id="ingr-name-<?= $i ?>">
                        <?php
                        $cant = rtrim(rtrim((string)$ing['cantidad_gramos'], '0'), '.');
                        echo $cant . 'g ' . htmlspecialchars($ing['nombre']);
                        ?>
                    </div>
                    <div class="ingr-meta" id="ingr-meta-<?= $i ?>"><?= $ing['calorias_calc'] ?> kcal • <?= $ing['proteina_calc'] ?>g prot</div>
                </div>
                <?php if ($receta['permitir_ia_swap']): ?>
                <button class="btn-swap"
                        onclick="abrirSwap('<?= htmlspecialchars(addslashes($ing['nombre'])) ?>', <?= $ing['cantidad_gramos'] ?>, <?= $i ?>)"
                        title="Sustitución IA">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="17 1 21 5 17 9"/>
                        <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                        <polyline points="7 23 3 19 7 15"/>
                        <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                    </svg>
                </button>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($pasos)): ?>
        <h2 class="section-title">Instrucciones</h2>
        <ol class="steps-list">
            <?php foreach ($pasos as $n => $paso): ?>
            <li class="step-item">
                <div class="step-num"><?= $n + 1 ?></div>
                <div class="step-text"><?= htmlspecialchars($paso) ?></div>
            </li>
            <?php endforeach; ?>
        </ol>
        <?php endif; ?>

    </div>

    <div class="floating-action">
        <button class="btn-registrar" onclick="abrirRegistro()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
                <path d="M9 16l2 2 4-4"/>
            </svg>
            Registrar Comida
        </button>
    </div>

</div>

<!-- MODAL TIPO DE COMIDA -->
<div class="modal-overlay" id="registroModal">
    <div class="modal-sheet" style="padding:0 1.5rem 2rem">
        <div class="modal-handle"></div>
        <div class="modal-title-text" style="margin-bottom:0.3rem">Registrar en tu Diario</div>
        <p class="modal-subtitle">¿En qué momento del día fue?</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1.5rem">
            <?php foreach(['Desayuno','Comida','Cena','Snack'] as $tipo): ?>
            <button class="btn-tipo-comida" onclick="guardarReceta('<?= $tipo ?>')"
                    style="padding:0.9rem;border:1.5px solid var(--color-border);border-radius:14px;
                           background:#fff;font-size:0.95rem;font-weight:600;
                           font-family:'Inter',sans-serif;cursor:pointer;
                           transition:border-color 0.15s,background 0.15s;">
                <?= ['Desayuno'=>'☀️','Comida'=>'🌱','Cena'=>'🌙','Snack'=>'🍪'][$tipo] ?? '' ?> <?= $tipo ?>
            </button>
            <?php endforeach; ?>
        </div>
        <button class="btn-cancelar" onclick="cerrarRegistro()">Cancelar</button>
    </div>
</div>

<!-- MODAL SUSTITUCIÓN IA -->
<div class="modal-overlay" id="swapModal">
    <div class="modal-sheet">
        <div class="modal-handle"></div>
        <div class="modal-title-text" id="swapTitle">Sustituir Ingrediente</div>
        <p class="modal-subtitle" id="swapSubtitle">Buscando alternativas...</p>
        <ul class="swap-options-list" id="swapOptionsList"></ul>
        <button class="btn-cancelar" onclick="cerrarSwap()">Cancelar</button>
    </div>
</div>

<script>
    // Inyectar base estática para JS
    window.NutriRecetaActual = {
        titulo:   '<?= addslashes(htmlspecialchars($receta['titulo'])) ?>',
        calorias: <?= (int)   ($receta['calorias_totales'] ?? 0) ?>,
        proteina: <?= (float) ($receta['proteina_total']   ?? 0) ?>,
        carbs:    <?= (float) ($receta['carbs_total']      ?? 0) ?>,
        grasas:   <?= (float) ($receta['grasas_total']     ?? 0) ?>
    };
</script>
<script src="../assets/js/receta_detalle.js?v=<?= time() ?>"></script>

</body>
</html>