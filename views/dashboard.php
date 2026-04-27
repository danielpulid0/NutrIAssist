<?php
require_once '../controllers/dashboard_controller.php';

$page_title = 'NutrIAssist - Inicio';
$extra_css  = '../assets/css/dashboard.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">
    
    <div class="header-dashboard">
        <div class="user-greeting">
            <p><?= htmlspecialchars($saludo) ?></p>
            <h1><?= htmlspecialchars($nombre_usuario) ?></h1>
        </div>
        <div class="header-date-box">
            <p class="header-date-label">HOY</p>
            <p class="header-date-value"><?= $label_hoy ?></p>
        </div>
    </div>

    <!-- EL ANILLO CIRCULAR DE CALORÍAS -->
    <div class="ring-container">
        <div class="calorie-ring" style="background: conic-gradient(var(--color-malachite) <?= $porcentaje_anillo ?>%, var(--color-mint) <?= $porcentaje_anillo ?>% 100%);">
            <div class="ring-inner">
                <p class="ring-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-malachite)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z"/><circle cx="12" cy="9" r="2.5" fill="var(--color-malachite)" stroke="none"/></svg></p>
                <h2><?= number_format($calorias_consumidas) ?></h2>
                <p class="ring-subtitle">de <?= number_format($meta_calorias) ?> kcal</p>
                <div class="ring-badge">
                    <?= number_format($calorias_restantes) ?> restantes
                </div>
            </div>
        </div>
    </div>

    <div class="macros-list">
        <!-- Proteínas -->
        <div class="macro-card-h">
            <div class="macro-header-h">
                <div class="macro-title-h">
                    <span class="macro-dot" style="background-color: #3b82f6;"></span>
                    Proteínas
                </div>
                <div class="macro-val-h"><?= $pro_consumidas ?> / 150g</div>
            </div>
            <div class="macro-bar-bg">
                <div class="macro-bar-fill" style="width: <?= $pro_p ?>%; background-color: #3b82f6;"></div>
            </div>
        </div>
        
        <!-- Carbohidratos -->
        <div class="macro-card-h">
            <div class="macro-header-h">
                <div class="macro-title-h">
                    <span class="macro-dot" style="background-color: #f97316;"></span>
                    Carbohidratos
                </div>
                <div class="macro-val-h"><?= $carbs_consumidas ?> / 220g</div>
            </div>
            <div class="macro-bar-bg">
                <div class="macro-bar-fill" style="width: <?= $carbs_p ?>%; background-color: #f97316;"></div>
            </div>
        </div>

        <!-- Grasas -->
        <div class="macro-card-h">
            <div class="macro-header-h">
                <div class="macro-title-h">
                    <span class="macro-dot" style="background-color: #eab308;"></span>
                    Grasas
                </div>
                <div class="macro-val-h"><?= $grasas_consumidas ?> / 70g</div>
            </div>
            <div class="macro-bar-bg">
                <div class="macro-bar-fill" style="width: <?= $grasas_p ?>%; background-color: #eab308;"></div>
            </div>
        </div>
    </div>

    <?php if ($receta_sugerida): 
        $etiquetas_array = json_decode($receta_sugerida['etiquetas'], true) ?? [];
        $costo_txt = 'Medio';
        $tag_visible = '';

        foreach ($etiquetas_array as $etq) {
            if (in_array(strtolower($etq), ['economico', 'económico', 'medio', 'costoso'])) {
                if ($etq === 'Economico') $etq = 'Económico';
                $costo_txt = $etq;
            } else {
                if (empty($tag_visible)) $tag_visible = $etq;
            }
        }
    ?>
    <h2 class="section-title">Sugerencia para la Cena</h2>
    <a href="receta_detalle.php?id=<?= $receta_sugerida['id_receta'] ?>" class="sug-link">
        <div class="suggestion-card">
            <img src="<?= (strpos($receta_sugerida['imagen_url'], 'http') === 0) ? htmlspecialchars($receta_sugerida['imagen_url']) : '../assets/img/' . htmlspecialchars($receta_sugerida['imagen_url']) ?>" alt="<?= htmlspecialchars($receta_sugerida['titulo']) ?>" class="suggestion-img">
            <div class="suggestion-info sug-info-box">
                <h3 class="sug-title sug-title-no-margin"><?= htmlspecialchars($receta_sugerida['titulo']) ?></h3>
                
                <div class="sug-meta-row">
                    <?php if ($receta_sugerida['tiempo_prep_min']): ?>
                    <span class="sug-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?= $receta_sugerida['tiempo_prep_min'] ?> min
                    </span>
                    <?php endif; ?>
                    <span class="sug-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/>
                        </svg>
                        <?= htmlspecialchars($costo_txt) ?>
                    </span>
                </div>

                <div class="sug-tags sug-tags-box">
                    <span class="tag-light tag-green"><?= $receta_sugerida['calorias_totales'] ?> kcal</span>
                    <?php if(!empty($tag_visible)): ?>
                        <span class="tag-light tag-gray"><?= htmlspecialchars($tag_visible) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </a>
    <?php endif; ?>

    <a href="chat_ia.php" class="fab-chat">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    </a>

    <?php include 'includes/footer.php'; ?>

</div>

</body>
</html>