<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: recetas.php");
    exit();
}

require_once '../config/conexion.php';
$id_receta = (int) $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT * FROM Recetas WHERE id_receta = :id");
    $stmt->bindParam(':id', $id_receta, PDO::PARAM_INT);
    $stmt->execute();
    $receta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$receta) { header("Location: recetas.php"); exit(); }

    $stmt_ing = $conn->prepare("
        SELECT ir.id_ingrediente, ir.cantidad_gramos, a.nombre,
               ROUND(a.calorias_por_100g * ir.cantidad_gramos / 100) AS calorias_calc,
               ROUND(a.proteina_por_100g * ir.cantidad_gramos / 100, 1) AS proteina_calc
        FROM Ingredientes_Receta ir
        JOIN Alimentos a ON ir.id_alimento = a.id_alimento
        WHERE ir.id_receta = :id
    ");
    $stmt_ing->bindParam(':id', $id_receta, PDO::PARAM_INT);
    $stmt_ing->execute();
    $ingredientes = $stmt_ing->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de Base de Datos: " . $e->getMessage());
}

// Parsear instrucciones en pasos numerados
$pasos = [];
if (!empty($receta['instrucciones'])) {
    $partes = preg_split('/\d+\.\s+/', $receta['instrucciones'], -1, PREG_SPLIT_NO_EMPTY);
    foreach ($partes as $p) {
        $paso = trim($p);
        if ($paso) $pasos[] = $paso;
    }
}

// Extraer costo de etiquetas
$tags  = json_decode($receta['etiquetas'] ?? '[]', true) ?: [];
$costo = 'Medio';
foreach ($tags as $t) {
    if (in_array($t, ['Económico', 'Medio', 'Caro', 'Premium'])) { $costo = $t; break; }
}

$tiempo_txt = $receta['tiempo_prep_min'] ? $receta['tiempo_prep_min'] . ' min' : '—';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist – <?= htmlspecialchars($receta['titulo']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        * { box-sizing: border-box; }
        body { background: var(--color-bg-app); }
        .mobile-container { padding: 0; min-height: 100vh; background: var(--color-bg-app); }

        /* ── HEADER ── */
        .detail-header {
            display: flex; align-items: center;
            padding: 1.1rem 1.25rem 0.9rem;
            background: #fff; border-bottom: 1px solid var(--color-border);
            position: sticky; top: 0; z-index: 50;
        }
        .btn-back {
            width: 36px; height: 36px; display: flex;
            align-items: center; justify-content: center;
            text-decoration: none; color: var(--color-text-dark);
            border-radius: 50%; transition: background 0.15s; flex-shrink: 0;
        }
        .btn-back:hover { background: var(--color-bg-app); }
        .btn-back svg { width: 20px; height: 20px; }
        .header-title { flex: 1; text-align: center; font-size: 1rem; font-weight: 700; color: var(--color-text-dark); }
        .header-spacer { width: 36px; flex-shrink: 0; }

        /* ── HERO ── */
        .hero-section { position: relative; width: 100%; height: 260px; background: #e8f5e9; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .hero-section img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hero-emoji { font-size: 5rem; }
        .hero-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.72) 40%, transparent 100%); }
        .hero-title { position: absolute; bottom: 1.25rem; left: 1.25rem; right: 1.25rem; font-size: 1.5rem; font-weight: 800; color: #fff; line-height: 1.2; text-shadow: 0 1px 4px rgba(0,0,0,0.3); }

        /* ── CONTENT ── */
        .recipe-content { background: #fff; padding: 1.5rem 1.25rem; padding-bottom: 110px; }

        /* Info pills */
        .info-pills { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; margin-bottom: 2rem; }
        .info-pill { border: 1px solid var(--color-border); border-radius: 12px; padding: 0.75rem 0.5rem; text-align: center; }
        .pill-icon { display: flex; align-items: center; justify-content: center; margin-bottom: 0.3rem; color: var(--color-malachite); }
        .pill-icon svg { width: 20px; height: 20px; }
        .pill-label { font-size: 0.68rem; color: var(--color-text-gray); display: block; margin-bottom: 2px; }
        .pill-value { font-size: 0.92rem; font-weight: 700; color: var(--color-text-dark); }

        /* ── SECCIONES ── */
        .section-title { font-size: 1.1rem; font-weight: 700; color: var(--color-text-dark); margin: 0 0 0.85rem; }

        /* ── INGREDIENTES ── */
        .ingredient-list { list-style: none; padding: 0; margin: 0 0 2rem; }
        .ingredient-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid var(--color-border); }
        .ingredient-item:last-child { border-bottom: none; }
        .ingr-check { width: 20px; height: 20px; flex-shrink: 0; border: 1.5px solid var(--color-border); border-radius: 5px; cursor: pointer; display: flex; align-items: center; justify-content: center; background: #fff; transition: background 0.15s, border-color 0.15s; }
        .ingr-check.done { background: var(--color-malachite); border-color: var(--color-malachite); }
        .ingr-check.done::after { content: ''; display: block; width: 5px; height: 9px; border: 2px solid #fff; border-top: none; border-left: none; transform: rotate(45deg) translateY(-1px); }
        .ingr-text { flex: 1; min-width: 0; }
        .ingr-name { font-size: 0.92rem; font-weight: 500; color: var(--color-text-dark); }
        .ingr-name.done-text { text-decoration: line-through; color: var(--color-text-gray); }
        .ingr-meta { font-size: 0.75rem; color: var(--color-text-gray); }
        .btn-swap { background: none; border: 1px solid var(--color-border); border-radius: 8px; padding: 0.3rem 0.45rem; cursor: pointer; color: var(--color-text-gray); flex-shrink: 0; transition: border-color 0.15s, color 0.15s; }
        .btn-swap:hover { border-color: var(--color-malachite); color: var(--color-malachite); }
        .btn-swap svg { width: 15px; height: 15px; display: block; }

        /* ── INSTRUCCIONES ── */
        .steps-list { list-style: none; padding: 0; margin: 0 0 2rem; }
        .step-item { display: flex; gap: 1rem; margin-bottom: 1.25rem; align-items: flex-start; }
        .step-num { width: 32px; height: 32px; border-radius: 50%; background: var(--color-malachite); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; flex-shrink: 0; }
        .step-text { font-size: 0.92rem; line-height: 1.6; color: var(--color-text-dark); padding-top: 5px; }

        /* ── FLOATING ACTION ── */
        .floating-action { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid var(--color-border); padding: 1rem 1.25rem calc(1rem + env(safe-area-inset-bottom)); z-index: 100; }
        .btn-registrar { width: 100%; background: var(--color-malachite); color: #fff; border: none; border-radius: 99px; padding: 1rem; font-size: 1rem; font-weight: 700; font-family: 'Inter', sans-serif; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.6rem; transition: background 0.15s; }
        .btn-registrar:hover { background: #0db844; }
        .btn-registrar svg { width: 20px; height: 20px; }

        /* ── MODAL IA SWAP ── */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: flex-end; justify-content: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal-sheet { background: #fff; width: 100%; max-width: 480px; border-radius: 24px 24px 0 0; padding: 0 1.5rem 2rem; animation: slideUp 0.3s cubic-bezier(.32,.72,0,1); max-height: 85vh; overflow-y: auto; }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

        .modal-handle { width: 36px; height: 4px; border-radius: 99px; background: #E5E7EB; margin: 12px auto 20px; }
        .modal-title-text { font-size: 1.15rem; font-weight: 800; color: var(--color-text-dark); margin-bottom: 0.3rem; }
        .modal-subtitle { font-size: 0.85rem; color: var(--color-text-gray); margin-bottom: 1.25rem; }

        /* Lista de opciones */
        .swap-options-list { list-style: none; padding: 0; margin: 0 0 1.5rem; }
        .swap-option { display: flex; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--color-border); gap: 0.75rem; }
        .swap-option:last-child { border-bottom: none; }
        .swap-option-info { flex: 1; min-width: 0; }
        .swap-option-top { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; flex-wrap: wrap; }
        .swap-nombre { font-size: 0.95rem; font-weight: 700; color: var(--color-text-dark); }
        .badge-recomendado { background: var(--color-mint); color: var(--color-malachite); border: 1px solid var(--color-spring); font-size: 0.62rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 99px; letter-spacing: 0.04em; white-space: nowrap; }
        .swap-nota { font-size: 0.78rem; color: var(--color-text-gray); }
        .btn-elegir { background: #fff; border: 1.5px solid var(--color-border); color: var(--color-text-dark); border-radius: 99px; padding: 0.45rem 1rem; font-size: 0.85rem; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: border-color 0.15s, color 0.15s; }
        .btn-elegir:hover { border-color: var(--color-malachite); color: var(--color-malachite); }
        .swap-loading { text-align: center; padding: 1.5rem 0; color: var(--color-text-gray); font-size: 0.9rem; }

        .btn-cancelar { width: 100%; background: none; border: none; color: var(--color-text-gray); font-size: 0.95rem; font-family: 'Inter', sans-serif; cursor: pointer; padding: 0.5rem; }
        .btn-cancelar:hover { color: var(--color-text-dark); }
    </style>
</head>
<body>
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
        <img src="../assets/img/recetas/<?= htmlspecialchars($receta['imagen_url']) ?>"
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
                <div class="ingr-check" id="check-<?= $i ?>"
                     onclick="toggleIngrediente(<?= $i ?>)"
                     role="checkbox" aria-checked="false"></div>
                <div class="ingr-text">
                    <div class="ingr-name" id="ingr-name-<?= $i ?>">
                        <?php
                        $cant = rtrim(rtrim((string)$ing['cantidad_gramos'], '0'), '.');
                        echo $cant . 'g ' . htmlspecialchars($ing['nombre']);
                        ?>
                    </div>
                    <div class="ingr-meta"><?= $ing['calorias_calc'] ?> kcal • <?= $ing['proteina_calc'] ?>g prot</div>
                </div>
                <?php if ($receta['permitir_ia_swap']): ?>
                <button class="btn-swap"
                        onclick="abrirSwap('<?= htmlspecialchars(addslashes($ing['nombre'])) ?>', <?= $ing['cantidad_gramos'] ?>)"
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
        <form action="../controllers/guardar_receta_diario.php" method="POST">
            <input type="hidden" name="id_receta" value="<?= $receta['id_receta'] ?>">
            <button type="submit" class="btn-registrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <path d="M9 16l2 2 4-4"/>
                </svg>
                Registrar Comida
            </button>
        </form>
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
    // ── Checkboxes ────────────────────────────────────────────────────────
    function toggleIngrediente(i) {
        const check = document.getElementById('check-' + i);
        const label = document.getElementById('ingr-name-' + i);
        const done  = check.classList.toggle('done');
        check.setAttribute('aria-checked', done);
        label.classList.toggle('done-text', done);
    }

    // ── Modal Swap ────────────────────────────────────────────────────────
    const swapModal = document.getElementById('swapModal');
    const swapTitle = document.getElementById('swapTitle');
    const swapSub   = document.getElementById('swapSubtitle');
    const swapList  = document.getElementById('swapOptionsList');

    let _ing = '', _gr = 0;

    function abrirSwap(nombre, gramos) {
        _ing = nombre; _gr = gramos;
        swapTitle.textContent = 'Sustituir ' + nombre;
        swapSub.textContent   = 'Buscando alternativas...';
        swapList.innerHTML    = '<li class="swap-loading">⏳ Gemma está analizando alternativas...</li>';
        swapModal.classList.add('active');
        pedirSwap(nombre, gramos);
    }

    function pedirSwap(nombre, gramos) {
        fetch('../controllers/ia_swap.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ ingrediente: nombre, gramos: gramos })
        })
        .then(r => r.json())
        .then(json => {
            if (json.status === 'success') {
                swapSub.textContent  = json.subtitulo || 'Sugerencias basadas en tus macros:';
                swapList.innerHTML   = '';
                window._swapOpciones = json.data;

                json.data.forEach((op, idx) => {
                    const li = document.createElement('li');
                    li.className = 'swap-option';
                    li.innerHTML = `
                        <div class="swap-option-info">
                            <div class="swap-option-top">
                                <span class="swap-nombre">${esc(op.nombre)}</span>
                                ${op.recomendado ? '<span class="badge-recomendado">RECOMENDADO</span>' : ''}
                            </div>
                            <div class="swap-nota">${esc(op.nota)}</div>
                        </div>
                        <button class="btn-elegir" onclick="elegirSwap(${idx})">Elegir</button>
                    `;
                    swapList.appendChild(li);
                });
            } else {
                swapList.innerHTML = `
                    <li style="padding:1rem 0;text-align:center;color:#EF4444">
                        ⚠️ ${esc(json.message || 'Error')}
                        <br><button onclick="pedirSwap('${esc(_ing)}',${_gr})"
                            style="margin-top:0.5rem;background:none;border:1px solid #ddd;
                                   border-radius:8px;padding:0.3rem 0.8rem;cursor:pointer">
                            🔄 Reintentar</button>
                    </li>`;
            }
        })
        .catch(() => {
            swapList.innerHTML = `
                <li style="padding:1rem 0;text-align:center;color:#EF4444">
                    ⚠️ Error de conexión
                    <br><button onclick="pedirSwap('${esc(_ing)}',${_gr})"
                        style="margin-top:0.5rem;background:none;border:1px solid #ddd;
                               border-radius:8px;padding:0.3rem 0.8rem;cursor:pointer">
                        🔄 Reintentar</button>
                </li>`;
        });
    }

    function elegirSwap(idx) {
        const op = (window._swapOpciones || [])[idx];
        if (!op) return;
        cerrarSwap();
        const t = document.createElement('div');
        t.textContent = '✓ Sustituido por: ' + op.nombre;
        Object.assign(t.style, {
            position:'fixed', bottom:'110px', left:'50%', transform:'translateX(-50%)',
            background:'#1F2937', color:'#fff', padding:'0.6rem 1.2rem',
            borderRadius:'99px', fontSize:'0.85rem', fontWeight:'500',
            zIndex:'2000', whiteSpace:'nowrap', transition:'opacity 0.3s'
        });
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2200);
    }

    function esc(str) {
        return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function cerrarSwap() { swapModal.classList.remove('active'); }
    swapModal.addEventListener('click', e => { if (e.target === swapModal) cerrarSwap(); });
</script>
</body>
</html>