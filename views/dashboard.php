<?php
session_start();

// Validar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.html");
    exit();
}

// Rescatar datos de la memoria del servidor
$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Daniel';
$meta_calorias = $_SESSION['meta_calorias'] ?? 2000;

// Datos simulados (Hardcoded) – Fase 4 vendrá de MySQL
$calorias_consumidas = 1600;
$calorias_restantes = $meta_calorias - $calorias_consumidas;

// Porcentaje para el anillo SVG
$porcentaje_anillo = min(($calorias_consumidas / $meta_calorias) * 100, 100);

// Datos de macros (simulados)
$proteinas = ['actual' => 90, 'meta' => 150];
$carbohidratos = ['actual' => 120, 'meta' => 220];
$grasas = ['actual' => 35, 'meta' => 70];

// Cálculos para el anillo SVG
$radio = 90;           // radio del trazo
$cx = 110;          // centro x del SVG
$cy = 110;          // centro y del SVG
$circunf = 2 * M_PI * $radio;       // circunferencia total
$dasharray = $circunf;
$dashoffset = $circunf * (1 - $porcentaje_anillo / 100);

// Fecha actual en español
$dias_es = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$meses_es = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$dia_semana = ucfirst($dias_es[date('w')]);
$dia_num = date('j');
$mes = $meses_es[(int) date('n')];
$fecha_str = "Mié, {$dia_num} " . ucfirst($mes); // Formato fiel a la captura

// Saludo según hora
$hora = (int) date('G');
if ($hora < 12)
    $saludo = 'Buenos días';
elseif ($hora < 20)
    $saludo = 'Buenas tardes';
else
    $saludo = 'Buenas noches';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist – Inicio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">

    <style>
        /* ── LAYOUT ── */
        .mobile-container {
            padding-bottom: 80px;
        }

        /* ── HEADER ── */
        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .dash-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dash-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a8edea, #fed6e3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .dash-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dash-greeting p {
            font-size: 0.82rem;
            color: var(--color-text-gray);
            margin-bottom: 1px;
        }

        .dash-greeting h1 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            color: var(--color-text-dark);
        }

        .dash-date {
            text-align: right;
        }

        .dash-date .hoy-badge {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--color-malachite);
            letter-spacing: 0.05em;
        }

        .dash-date .fecha {
            font-size: 0.85rem;
            color: var(--color-text-gray);
            margin-top: 2px;
        }

        /* ── ZONA DEL ANILLO ── */
        .ring-section {
            background-color: var(--color-bg-app);
            border-radius: 20px;
            padding: 1.5rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .ring-wrap {
            position: relative;
            width: 220px;
            height: 220px;
        }

        .ring-wrap svg {
            width: 220px;
            height: 220px;
            transform: rotate(-90deg);
        }

        .ring-track {
            fill: none;
            stroke: #E5E7EB;
            stroke-width: 18;
            stroke-linecap: round;
        }

        .ring-fill {
            fill: none;
            stroke: url(#gradGreen);
            stroke-width: 18;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.8s ease;
        }

        .ring-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            line-height: 1.2;
        }

        .ring-icon {
            font-size: 1.4rem;
            margin-bottom: 2px;
        }

        .ring-kcal {
            font-size: 2.4rem;
            font-weight: 800;
            color: var(--color-text-dark);
            letter-spacing: -0.02em;
        }

        .ring-label {
            font-size: 0.8rem;
            color: var(--color-text-gray);
            margin-top: 1px;
        }

        .ring-restantes {
            margin-top: 0.85rem;
            background-color: #D7FFE4;
            color: #11CF50;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 0.35rem 1rem;
            border-radius: 99px;
        }

        /* ── MACROS ── */
        .macros-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-bottom: 1.5rem;
        }

        .macro-row {
            background-color: #fff;
            border: 1px solid var(--color-border);
            border-radius: 14px;
            padding: 0.85rem 1rem;
        }

        .macro-row-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .macro-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--color-text-dark);
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .macro-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .macro-nums {
            font-size: 0.85rem;
            color: var(--color-text-gray);
            font-weight: 500;
        }

        .macro-bar-bg {
            width: 100%;
            height: 7px;
            background-color: #F3F4F6;
            border-radius: 99px;
            overflow: hidden;
        }

        .macro-bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.6s ease;
        }

        /* ── SUGERENCIA ── */
        .sugerencia-titulo {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 0.85rem;
        }

        .sugerencia-card {
            background-color: #fff;
            border: 1px solid var(--color-border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            gap: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .sugerencia-img {
            width: 120px;
            min-height: 140px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .sugerencia-body {
            padding: 0.85rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sugerencia-meta {
            font-size: 0.78rem;
            color: var(--color-text-gray);
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .sugerencia-meta .star {
            color: #F59E0B;
        }

        .sugerencia-nombre {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--color-text-dark);
            line-height: 1.3;
            margin-bottom: 0.35rem;
        }

        .sugerencia-desc {
            font-size: 0.78rem;
            color: var(--color-text-gray);
            line-height: 1.4;
            margin-bottom: 0.55rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sugerencia-tags {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .tag {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 99px;
            background-color: var(--color-bg-app);
            color: var(--color-text-gray);
        }

        .tag.green {
            background-color: #D7FFE4;
            color: #11CF50;
        }

        /* ── FAB CHAT ── */
        .fab-chat {
            position: fixed;
            bottom: 90px;
            right: calc(50% - 215px + 16px);
            /* alineado al borde derecho del contenedor */
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background-color: var(--color-malachite);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(17, 207, 80, 0.45);
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            z-index: 900;
        }

        .fab-chat:active {
            transform: scale(0.93);
            box-shadow: 0 2px 8px rgba(17, 207, 80, 0.3);
        }

        .fab-chat svg {
            width: 26px;
            height: 26px;
        }

        /* ── BOTTOM NAV ── */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 430px;
            background-color: #fff;
            border-top: 1px solid var(--color-border);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0.6rem 0 calc(0.6rem + env(safe-area-inset-bottom, 0px));
            z-index: 1000;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--color-text-gray);
            font-size: 0.72rem;
            font-weight: 500;
            gap: 3px;
            flex: 1;
            transition: color 0.2s;
        }

        .nav-item.active {
            color: var(--color-malachite);
        }

        .nav-item svg {
            width: 24px;
            height: 24px;
            stroke-width: 1.8;
        }

        .nav-item.active svg {
            stroke-width: 2.4;
        }

        /* Responsive FAB cuando el viewport es muy ancho */
        @media (min-width: 430px) {
            .fab-chat {
                right: calc(50% - 215px + 16px);
            }
        }
    </style>
</head>

<body>

    <div class="mobile-container">

        <!-- HEADER -->
        <div class="dash-header">
            <div class="dash-user">
                <div class="dash-avatar">🧑</div>
                <div class="dash-greeting">
                    <p><?= htmlspecialchars($saludo) ?></p>
                    <h1><?= htmlspecialchars($nombre_usuario) ?></h1>
                </div>
            </div>
            <div class="dash-date">
                <div class="hoy-badge">HOY</div>
                <div class="fecha"><?= $fecha_str ?></div>
            </div>
        </div>

        <!-- ANILLO DE CALORÍAS -->
        <div class="ring-section">
            <div class="ring-wrap">
                <svg viewBox="0 0 220 220" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="gradGreen" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" style="stop-color:#37F677;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#11CF50;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <!-- Pista de fondo -->
                    <circle class="ring-track" cx="110" cy="110" r="<?= $radio ?>" />
                    <!-- Arco de progreso -->
                    <circle class="ring-fill" cx="110" cy="110" r="<?= $radio ?>"
                        stroke-dasharray="<?= round($circunf, 2) ?>" stroke-dashoffset="<?= round($dashoffset, 2) ?>" />
                </svg>
                <div class="ring-center">
                    <div class="ring-icon">🔥</div>
                    <div class="ring-kcal"><?= number_format($calorias_consumidas) ?></div>
                    <div class="ring-label">de <?= number_format($meta_calorias) ?> kcal</div>
                </div>
            </div>
            <div class="ring-restantes"><?= number_format($calorias_restantes) ?> restantes</div>
        </div>

        <!-- MACRONUTRIENTES -->
        <div class="macros-list">

            <?php
            $macros = [
                [
                    'nombre' => 'Proteínas',
                    'actual' => $proteinas['actual'],
                    'meta' => $proteinas['meta'],
                    'color' => '#3B82F6',   // azul
                    'dot' => '#3B82F6',
                ],
                [
                    'nombre' => 'Carbohidratos',
                    'actual' => $carbohidratos['actual'],
                    'meta' => $carbohidratos['meta'],
                    'color' => '#F97316',   // naranja
                    'dot' => '#F97316',
                ],
                [
                    'nombre' => 'Grasas',
                    'actual' => $grasas['actual'],
                    'meta' => $grasas['meta'],
                    'color' => '#FBBF24',   // amarillo
                    'dot' => '#FBBF24',
                ],
            ];
            foreach ($macros as $m):
                $pct = min(($m['actual'] / $m['meta']) * 100, 100);
                ?>
                <div class="macro-row">
                    <div class="macro-row-top">
                        <div class="macro-name">
                            <span class="macro-dot" style="background:<?= $m['dot'] ?>"></span>
                            <?= htmlspecialchars($m['nombre']) ?>
                        </div>
                        <div class="macro-nums"><?= $m['actual'] ?> / <?= $m['meta'] ?>g</div>
                    </div>
                    <div class="macro-bar-bg">
                        <div class="macro-bar-fill" style="width:<?= round($pct) ?>%; background:<?= $m['color'] ?>">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

        <!-- SUGERENCIA PARA LA CENA -->
        <div class="sugerencia-titulo">Sugerencia para la Cena</div>

        <div class="sugerencia-card">
            <!-- Imagen generada / placeholder verde -->
            <img class="sugerencia-img" src="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=240&q=80"
                alt="Ensalada de Quinoa y Aguacate"
                onerror="this.style.background='#D7FFE4';this.removeAttribute('src');">
            <div class="sugerencia-body">
                <div>
                    <div class="sugerencia-meta">
                        <span class="star">★</span> 4.8 &bull; 25 min
                    </div>
                    <div class="sugerencia-nombre">Ensalada de Quinoa y Aguacate</div>
                    <div class="sugerencia-desc">Una cena ligera, rica en fibra y grasas saludables. Perfecta para...
                    </div>
                </div>
                <div class="sugerencia-tags">
                    <span class="tag green">340 kcal</span>
                    <span class="tag">Vegetariano</span>
                </div>
            </div>
        </div>

        <!-- FAB: botón de chat flotante -->
        <a href="#" class="fab-chat" aria-label="Abrir asistente IA">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8 10h.01M12 10h.01M16 10h.01M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z" />
            </svg>
        </a>

    </div><!-- /mobile-container -->

    <!-- BARRA DE NAVEGACIÓN INFERIOR -->
    <nav class="bottom-nav" role="navigation" aria-label="Navegación principal">

        <!-- Inicio (activo) -->
        <a href="dashboard.php" id="nav-inicio"
            class="nav-item <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 9.75L12 3l9 6.75V21a.75.75 0 01-.75.75H15.75a.75.75 0 01-.75-.75v-4.5H9v4.5a.75.75 0 01-.75.75H3.75A.75.75 0 013 21V9.75z" />
            </svg>
            Inicio
        </a>

        <!-- Diario -->
        <a href="#" id="nav-diario" class="nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Diario
        </a>

        <!-- Recetas -->
        <a href="#" id="nav-recetas" class="nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 8.25h15M4.5 12h15m-7.5 3.75h7.5" />
            </svg>
            Recetas
        </a>

        <!-- Perfil -->
        <a href="#" id="nav-perfil" class="nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            Perfil
        </a>

    </nav>

</body>

</html>