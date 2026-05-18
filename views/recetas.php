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
    <form method="GET" action="" class="search-wrapper" onsubmit="event.preventDefault();">
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
        <a href="#" class="chip <?= ($filtro_tag === '' && !$filtro_tiempo) ? 'active' : '' ?>" data-filter="todos">Todos</a>
        <a href="#" class="chip <?= $filtro_tiempo ? 'active' : '' ?>" data-filter="rapido">&lt; 20 min</a>

        <?php foreach ($todos_tags as $tag_actual): ?>
        <a href="#" class="chip <?= ($filtro_tag === $tag_actual && !$filtro_tiempo) ? 'active' : '' ?>" data-tag="<?= htmlspecialchars($tag_actual) ?>">
            <?= htmlspecialchars($tag_actual) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Grid de recetas -->
    <div class="recetas-grid">
        <!-- Estado vacío para filtros dinámicos -->
        <div class="empty-state" id="empty-state" style="display: none;">
            <div class="empty-state-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12h20M6 12V7a6 6 0 0 1 12 0v5"/>
                    <path d="M4 12c0 4.418 3.582 8 8 8s8-3.582 8-8"/>
                </svg>
            </div>
            <p>No se encontraron recetas con los filtros seleccionados.</p>
        </div>

        <?php foreach ($recetas as $receta_card): ?>
        <a href="receta_detalle.php?id=<?= $receta_card['id_receta'] ?>" 
           class="receta-card"
           data-titulo="<?= htmlspecialchars(strtolower($receta_card['titulo'])) ?>"
           data-tiempo="<?= (int)$receta_card['tiempo_prep_min'] ?>"
           data-tags='<?= json_encode(array_map('strtolower', $receta_card['tags'])) ?>'>

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
    // 1. Tutorial Check
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tutorial') === '1') {
        startTutorial();
    }

    // 2. Filtro Multiselección Dinámico (Cliente-JS)
    const searchBar = document.querySelector('.search-bar');
    const cards = document.querySelectorAll('.receta-card');
    const chipTodos = document.querySelector('[data-filter="todos"]');
    const chipRapido = document.querySelector('[data-filter="rapido"]');
    const tagChips = document.querySelectorAll('[data-tag]');
    const emptyState = document.getElementById('empty-state');

    // Estado del filtro
    const state = {
        search: searchBar ? searchBar.value.trim().toLowerCase() : '',
        rapido: false,
        tags: new Set()
    };

    // Cargar filtros iniciales desde la URL (si existen)
    const initialTag = urlParams.get('tag');
    const initialRapido = urlParams.get('rapido') === '1';
    
    if (initialTag) {
        state.tags.add(initialTag.toLowerCase());
        const matchingChip = Array.from(tagChips).find(c => c.getAttribute('data-tag').toLowerCase() === initialTag.toLowerCase());
        if (matchingChip) matchingChip.classList.add('active');
    }
    if (initialRapido) {
        state.rapido = true;
        if (chipRapido) chipRapido.classList.add('active');
    }
    if (state.tags.size > 0 || state.rapido || state.search) {
        if (chipTodos) chipTodos.classList.remove('active');
    }

    // Función principal para aplicar filtros
    function applyFilters() {
        let visibleCount = 0;

        cards.forEach(card => {
            const titulo = card.getAttribute('data-titulo') || '';
            const tiempo = parseInt(card.getAttribute('data-tiempo') || '0');
            const tags = JSON.parse(card.getAttribute('data-tags') || '[]');

            // A. Coincidencia de búsqueda
            const matchesSearch = !state.search || titulo.includes(state.search);

            // B. Coincidencia de tiempo
            const matchesTime = !state.rapido || tiempo < 20;

            // C. Coincidencia de tags (Todas las seleccionadas deben estar presentes - AND)
            const matchesTags = state.tags.size === 0 || Array.from(state.tags).every(t => tags.includes(t));

            if (matchesSearch && matchesTime && matchesTags) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Toggle Empty State
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        // Resaltar chip "Todos" si no hay filtros activos
        if (state.tags.size === 0 && !state.rapido) {
            if (chipTodos) chipTodos.classList.add('active');
        } else {
            if (chipTodos) chipTodos.classList.remove('active');
        }
    }

    // Escuchador de búsqueda en tiempo real
    if (searchBar) {
        searchBar.addEventListener('input', (e) => {
            state.search = e.target.value.trim().toLowerCase();
            applyFilters();
        });
    }

    // Escuchador para "Todos" (Reset)
    if (chipTodos) {
        chipTodos.addEventListener('click', (e) => {
            e.preventDefault();
            state.tags.clear();
            state.rapido = false;
            state.search = '';
            if (searchBar) searchBar.value = '';

            if (chipRapido) chipRapido.classList.remove('active');
            tagChips.forEach(c => c.classList.remove('active'));
            chipTodos.classList.add('active');

            applyFilters();
        });
    }

    // Escuchador para "Rápido (< 20 min)"
    if (chipRapido) {
        chipRapido.addEventListener('click', (e) => {
            e.preventDefault();
            state.rapido = !state.rapido;
            chipRapido.classList.toggle('active', state.rapido);
            applyFilters();
        });
    }

    // Escuchadores para chips de etiquetas
    tagChips.forEach(chip => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            const tagVal = chip.getAttribute('data-tag').toLowerCase();
            
            if (state.tags.has(tagVal)) {
                state.tags.delete(tagVal);
                chip.classList.remove('active');
            } else {
                state.tags.add(tagVal);
                chip.classList.add('active');
            }
            
            applyFilters();
        });
    });

    // Carga inicial
    applyFilters();
});
</script>

</body>
</html>