<?php
require_once '../controllers/dashboard_controller.php';

$page_title = 'NutrIAssist - Inicio';
$extra_css  = '../assets/css/dashboard.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">
    
    <div class="header-dashboard">
        <div class="user-greeting">
            <div style="display: flex; align-items: center; gap: 8px;">
                <p><?= htmlspecialchars($saludo) ?></p>
                <button onclick="startTutorial(false)" class="tutorial-trigger" title="Ver guía de esta página">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 1 7 7c0 2.38-1.19 4.47-3 5.74V17a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2v-2.26C6.19 13.47 5 11.38 5 9a7 7 0 0 1 7-7z"/></svg>
                </button>
            </div>
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
    <h2 class="section-title">Sugerencia para ti</h2>
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

<!-- Intro.js CSS & JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intro.js/7.2.0/introjs.min.css">
<style>
    /* Customizing Intro.js to match NutrIAssist's modern theme */
    .introjs-tooltip {
        background-color: var(--color-bg-card, #ffffff) !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15) !important;
        color: var(--color-text) !important;
        font-family: 'Inter', sans-serif !important;
        max-width: 90vw !important;
        min-width: 320px !important; /* Slightly larger base size */
        box-sizing: border-box !important;
        padding: 10px !important;
    }
    .introjs-tooltiptext {
        font-size: 15px !important;
        line-height: 1.6 !important;
        color: #1e293b !important; /* Higher contrast (Slate 800) */
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
    .introjs-tooltip-header {
        position: relative !important;
    }
    .introjs-button {
        border-radius: 10px !important;
        text-shadow: none !important;
        box-shadow: none !important;
        border: none !important;
        font-weight: 700 !important;
        font-family: 'Inter', sans-serif !important;
        padding: 10px 20px !important;
        transition: all 0.2s !important;
    }
    .introjs-nextbutton, .introjs-donebutton {
        background: var(--color-malachite) !important;
        color: white !important;
    }
    .introjs-prevbutton {
        background-color: #f1f5f9 !important;
        color: #475569 !important;
    }
    .introjs-skipbutton {
        position: absolute !important;
        top: 15px !important;
        right: 15px !important;
        color: #94a3b8 !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        text-decoration: none !important;
        line-height: 1 !important;
    }
    .introjs-bullets ul li a.active {
        background: var(--color-malachite) !important;
    }
    .introjs-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    body.dark-mode .introjs-tooltip {
        background-color: #1e293b !important;
        color: #f8fafc !important;
    }
    body.dark-mode .introjs-tooltiptext {
        color: #cbd5e1 !important;
    }
    body.dark-mode .introjs-prevbutton {
        background-color: #334155 !important;
        color: #f8fafc !important;
    }
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
function startTutorial(isMultiPage = true) {
    var intro = introJs();
    intro.setOptions({
        steps: [
            {
                title: '¡Bienvenido! 👋',
                intro: 'Esta es tu nueva central de salud. Hemos diseñado una experiencia rápida para que aprendas a usarla en segundos.',
                tooltipClass: 'welcome-step'
            },
            {
                element: document.querySelector('.ring-container'),
                title: 'Control de Energía',
                intro: 'Este anillo te muestra exactamente cuánto has comido y cuánto te queda para alcanzar tu meta diaria.'
            },
            {
                element: document.querySelector('.macros-list'),
                title: 'Tus Macros',
                intro: 'No solo importan las calorías. Aquí vigilamos tus proteínas, carbohidratos y grasas para que tu cuerpo rinda al máximo.'
            },
            <?php if ($receta_sugerida): ?>
            {
                element: document.querySelector('.suggestion-card'),
                title: 'Recomendación del día',
                intro: '¿No sabes qué comer? Nuestra IA selecciona cada día una receta perfecta para tus metas actuales.'
            },
            <?php endif; ?>
            {
                element: document.querySelector('.fab-chat'),
                title: 'Tu Asistente 24/7',
                intro: 'Pulsa aquí para hablar con nuestra IA. Puedes preguntarle recetas, dudas nutricionales o pedirle que registre algo por ti.',
                position: 'left'
            },
            {
                element: document.querySelectorAll('.nav-item')[1],
                title: 'Siguiente parada: El Diario',
                intro: isMultiPage ? '¡Pulsa aquí para registrar tus comidas! Haz clic en el ícono de Diario para continuar la guía allí.' : 'Desde aquí puedes ir a tu diario de comidas para registrar lo que consumes.',
                position: 'top'
            }
        ],
        nextLabel: 'Siguiente',
        prevLabel: 'Atrás',
        doneLabel: isMultiPage ? '¡Ir al Diario!' : '¡Entendido!',
        showSkipButton: true,
        skipLabel: 'Saltar guía',
        showBullets: true,
        overlayOpacity: 0.8
    });

    intro.oncomplete(function() {
        if (isMultiPage) {
            localStorage.setItem('nutriassist_tutorial_shown_v4', 'true');
            window.location.href = 'diario.php?tutorial=1';
        }
    });
    
    intro.onexit(function() {
        if (isMultiPage) {
            localStorage.setItem('nutriassist_tutorial_shown_v4', 'true');
        }
    });

    intro.start();
}

document.addEventListener("DOMContentLoaded", function() {
    // Force tutorial to show if the user just registered/onboarded
    <?php if (isset($_SESSION['mostrar_tutorial']) && $_SESSION['mostrar_tutorial']): ?>
        localStorage.removeItem('nutriassist_tutorial_shown_v4');
        localStorage.removeItem('active_tutorial_part');
        <?php unset($_SESSION['mostrar_tutorial']); ?>
    <?php endif; ?>

    // Check if the tutorial has already been shown
    if (!localStorage.getItem('nutriassist_tutorial_shown_v4')) {
        setTimeout(() => {
            startTutorial(true);
        }, 800);
    }
});
</script>

</body>
</html>