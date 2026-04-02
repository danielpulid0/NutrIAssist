<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}

require_once '../config/conexion.php';

// ─── Búsqueda / filtro por GET ─────────────────────────────────────────
$q          = trim($_GET['q'] ?? '');
$filtro_tag = trim($_GET['tag'] ?? '');

try {
    $sql = "SELECT id_receta, titulo, imagen_url, tiempo_prep_min,
                   calorias_totales, etiquetas
            FROM Recetas";
    $params = [];

    if ($q !== '') {
        $sql .= " WHERE titulo LIKE :q";
        $params[':q'] = '%' . $q . '%';
    }
    $sql .= " ORDER BY id_receta ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $recetas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recetas_raw = [];
}

// ─── Helpers ───────────────────────────────────────────────────────────
// Colores de tags conocidos
$tag_colores = [
    'Fácil'          => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
    'Vegetariano'    => ['bg' => '#DBEAFE', 'fg' => '#2563EB'],
    'Vegano'         => ['bg' => '#DBEAFE', 'fg' => '#2563EB'],
    'Keto'           => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
    '+Proteína'      => ['bg' => '#FEF3C7', 'fg' => '#D97706'],
    'Alta Proteína'  => ['bg' => '#FEF3C7', 'fg' => '#D97706'],
    'Bebida'         => ['bg' => '#F3E8FF', 'fg' => '#9333EA'],
    'Fibra'          => ['bg' => '#FFEDD5', 'fg' => '#EA580C'],
    'Snack'          => ['bg' => '#FCE7F3', 'fg' => '#DB2777'],
    'Favoritos'      => ['bg' => '#FEF3C7', 'fg' => '#D97706'],
    'Rápido'         => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
    'Premium'        => ['bg' => '#EDE9FE', 'fg' => '#7C3AED'],
    'Pre-Entreno'    => ['bg' => '#FFEDD5', 'fg' => '#EA580C'],
    'Equilibrado'    => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
    'Bajo en Carbs'  => ['bg' => '#DBEAFE', 'fg' => '#2563EB'],
    'Omega-3'        => ['bg' => '#DBEAFE', 'fg' => '#2563EB'],
    'Crujiente'      => ['bg' => '#FFEDD5', 'fg' => '#EA580C'],
    'Económico'      => ['bg' => '#DCFCE7', 'fg' => '#16A34A'],
];

$costos        = ['Económico', 'Medio', 'Caro', 'Premium'];
$tipos_comida  = ['Desayuno', 'Comida', 'Cena', 'Snack', 'Vegetariano', 'Vegano'];
$todos_tags    = [];

// Procesamos recetas y extraemos todos los tags para los chips de filtro
$recetas = [];
foreach ($recetas_raw as $r) {
    $tags = json_decode($r['etiquetas'] ?? '[]', true) ?: [];

    // Detectar costo en etiquetas
    $costo_txt = 'Medio';
    foreach ($tags as $t) {
        if (in_array($t, $costos)) { $costo_txt = $t; break; }
    }

    // Tag visible: el primero que no sea tipo de comida ni costo
    $tag_visible = null;
    $color_tag   = ['bg' => '#F3F4F6', 'fg' => '#6B7280'];
    foreach ($tags as $t) {
        if (!in_array($t, $costos) && !in_array($t, $tipos_comida)) {
            $tag_visible = $t;
            $color_tag   = $tag_colores[$t] ?? $color_tag;
            break;
        }
    }

    // Acumular tags únicos para el chip-bar de filtros
    foreach ($tags as $t) { $todos_tags[$t] = true; }

    // Aplicar filtro de tag si hay uno activo
    if ($filtro_tag !== '' && !in_array($filtro_tag, $tags)) continue;

    $recetas[] = array_merge($r, [
        'tags'       => $tags,
        'costo_txt'  => $costo_txt,
        'tag_visible'=> $tag_visible,
        'color_tag'  => $color_tag,
    ]);
}

$todos_tags = array_keys($todos_tags);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist – Recetas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        .mobile-container { background-color: var(--color-bg-app); padding-bottom: 90px; }

        /* ── TÍTULO ── */
        .page-title-center {
            text-align: center; font-size: 1.25rem; font-weight: 700;
            color: var(--color-text-dark); margin-bottom: 1rem;
        }

        /* ── SEARCH BAR ── */
        .search-wrapper {
            position: relative; margin-bottom: 1rem;
        }
        .search-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--color-text-gray);
            display: flex; align-items: center;
        }
        .search-bar {
            width: 100%; box-sizing: border-box;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            background: #fff; border: 1px solid var(--color-border);
            border-radius: 99px; font-size: 0.95rem;
            font-family: 'Inter', sans-serif; color: var(--color-text-dark);
            outline: none; transition: border-color 0.15s;
        }
        .search-bar:focus { border-color: var(--color-malachite); }
        .search-bar::placeholder { color: var(--color-text-gray); }

        /* ── CHIPS ── */
        .chips-container {
            display: flex; gap: 0.5rem; overflow-x: auto;
            padding-bottom: 0.5rem; margin-bottom: 1.1rem;
            scrollbar-width: none;
        }
        .chips-container::-webkit-scrollbar { display: none; }
        .chip {
            background: #fff; border: 1px solid var(--color-border);
            padding: 0.4rem 0.85rem; border-radius: 99px;
            font-size: 0.75rem; font-weight: 500;
            color: var(--color-text-dark); white-space: nowrap;
            cursor: pointer; text-decoration: none;
            transition: background 0.15s, border-color 0.15s;
            flex-shrink: 0;
        }
        .chip.active, .chip:hover {
            background: var(--color-mint);
            border-color: var(--color-malachite);
            color: var(--color-malachite);
        }

        /* ── GRID ── */
        .recetas-grid {
            display: grid; grid-template-columns: repeat(2, 1fr);
            gap: 0.9rem;
        }

        /* ── CARD ── */
        .receta-card {
            background: #fff; border: 1px solid var(--color-border);
            border-radius: 16px; overflow: hidden;
            text-decoration: none; color: inherit;
            display: flex; flex-direction: column;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .receta-card:active { transform: scale(0.97); }

        .receta-img-wrapper {
            width: 100%; aspect-ratio: 1; overflow: hidden;
            background: var(--color-mint);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem;
        }
        .receta-img-wrapper img {
            width: 100%; height: 100%; object-fit: cover;
            display: block;
        }

        .receta-info { padding: 0.75rem; flex: 1; display: flex; flex-direction: column; }

        .receta-title {
            font-size: 0.9rem; font-weight: 700;
            color: var(--color-text-dark); line-height: 1.25;
            margin-bottom: 0.4rem;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }

        .receta-meta {
            font-size: 0.7rem; color: var(--color-text-gray);
            display: flex; align-items: center; gap: 0.45rem;
            margin-bottom: 0.5rem; flex-wrap: wrap;
        }
        .receta-meta svg { width: 11px; height: 11px; flex-shrink: 0; }
        .meta-item { display: flex; align-items: center; gap: 2px; }

        .receta-footer {
            margin-top: auto; display: flex;
            justify-content: space-between; align-items: center;
        }
        .receta-kcal {
            font-size: 0.85rem; font-weight: 700;
            color: var(--color-malachite);
        }
        .receta-tag {
            font-size: 0.65rem; font-weight: 600;
            padding: 0.2rem 0.55rem; border-radius: 99px;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center; padding: 3rem 1rem;
            color: var(--color-text-gray); grid-column: 1 / -1;
        }
        .empty-state p { font-size: 0.95rem; margin-top: 0.5rem; }
    </style>
</head>
<body>
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
                <img src="../assets/img/recetas/<?= htmlspecialchars($r['imagen_url']) ?>"
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