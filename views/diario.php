<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist – Diario</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">

    <style>
        /* ── LAYOUT ── */
        .mobile-container {
            background-color: var(--color-bg-app);
            padding-bottom: 90px;
        }

        /* ── TÍTULO ── */
        .page-title-center {
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 1.1rem;
        }

        /* ── CALENDARIO ── */
        .calendar-card {
            background: #fff;
            border-radius: 16px;
            padding: 1rem 1.1rem;
            margin-bottom: 0.85rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }

        .cal-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.9rem;
        }

        .cal-nav-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--color-text-gray);
            font-size: 1.4rem;
            line-height: 1;
            padding: 0 0.25rem;
            transition: color 0.15s;
        }
        .cal-nav-btn:hover { color: var(--color-text-dark); }

        .cal-month-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--color-text-dark);
        }

        .cal-weekdays,
        .cal-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
        }

        .cal-weekdays {
            margin-bottom: 0.4rem;
        }

        .cal-weekdays span {
            font-size: 0.73rem;
            font-weight: 500;
            color: var(--color-text-gray);
            padding: 0.2rem 0;
        }

        .cal-days span {
            font-size: 0.9rem;
            color: var(--color-text-dark);
            cursor: pointer;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            transition: background 0.15s;
        }
        .cal-days span:hover {
            background: var(--color-mint);
        }
        .cal-days span.active {
            background-color: var(--color-malachite);
            color: #fff;
            font-weight: 700;
        }

        /* ── CALORÍAS TOTALES ── */
        .cals-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.1rem 1.25rem;
            margin-bottom: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }

        .cals-label {
            font-size: 0.78rem;
            color: var(--color-text-gray);
            margin-bottom: 0.3rem;
        }

        .cals-num {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--color-text-dark);
            letter-spacing: -0.02em;
        }

        .cals-meta {
            font-size: 0.85rem;
            color: var(--color-text-gray);
            font-weight: 400;
        }

        .cals-flame-ring {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2.5px solid var(--color-malachite);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        /* ── MEAL CARDS ── */
        .meal-card {
            background: #fff;
            border-radius: 16px;
            margin-bottom: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }

        .meal-header {
            display: flex;
            align-items: center;
            padding: 1rem 1.1rem;
            cursor: pointer;
            gap: 0.85rem;
            user-select: none;
        }

        /* Colored icon bubble */
        .meal-icon-bubble {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .meal-icon-bubble svg {
            width: 22px;
            height: 22px;
        }

        .meal-text {
            flex: 1;
            min-width: 0;
        }

        .meal-name {
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 2px;
        }

        .meal-kcal {
            font-size: 0.8rem;
            color: var(--color-text-gray);
        }

        .meal-pending-label {
            font-size: 0.8rem;
            color: var(--color-text-gray);
        }

        /* Chevron */
        .meal-chevron {
            color: var(--color-text-gray);
            flex-shrink: 0;
            transition: transform 0.25s ease;
        }
        .meal-chevron.up {
            transform: rotate(180deg);
        }

        /* Collapsible content */
        .meal-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
        }
        .meal-content.open {
            max-height: 500px;
        }

        .meal-divider {
            height: 1px;
            background: var(--color-border);
            margin: 0;
        }

        /* Food item row */
        .meal-item {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.25rem;
            gap: 0.7rem;
        }

        .meal-item-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--color-text-gray);
            flex-shrink: 0;
        }

        .meal-item-name {
            font-size: 0.88rem;
            color: var(--color-text-dark);
            flex: 1;
            font-weight: 500;
        }

        .meal-item-meta {
            font-size: 0.78rem;
            color: var(--color-text-gray);
            white-space: nowrap;
        }

        /* Confirm button row */
        .meal-confirm-row {
            display: flex;
            justify-content: flex-end;
            padding: 0.4rem 1rem 0.9rem;
        }

        .btn-confirmar {
            background-color: var(--color-malachite);
            color: #fff;
            border: none;
            border-radius: 99px;
            padding: 0.6rem 1.35rem;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: transform 0.15s, background 0.15s;
            font-family: 'Inter', sans-serif;
        }
        .btn-confirmar:hover { background: #0db844; }
        .btn-confirmar:active { transform: scale(0.97); }
    </style>
</head>
<body>

<div class="mobile-container">

    <!-- TÍTULO -->
    <h1 class="page-title-center">Diario de Comidas</h1>

    <!-- CALENDARIO SEMANAL -->
    <div class="calendar-card">
        <div class="cal-nav">
            <button class="cal-nav-btn" aria-label="Mes anterior">&#8249;</button>
            <span class="cal-month-label">Octubre 2023</span>
            <button class="cal-nav-btn" aria-label="Mes siguiente">&#8250;</button>
        </div>
        <div class="cal-weekdays">
            <span>D</span><span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span>
        </div>
        <div class="cal-days">
            <span>1</span>
            <span>2</span>
            <span>3</span>
            <span>4</span>
            <span class="active">5</span>
            <span>6</span>
            <span>7</span>
        </div>
    </div>

    <!-- CALORÍAS TOTALES -->
    <div class="cals-card">
        <div>
            <div class="cals-label">Calorías Totales</div>
            <div>
                <span class="cals-num">1,320</span>
                <span class="cals-meta"> / 2,000 kcal</span>
            </div>
        </div>
        <div class="cals-flame-ring">🔥</div>
    </div>

    <!-- ── DESAYUNO (colapsado) ── -->
    <div class="meal-card">
        <div class="meal-header" id="hdr-desayuno" onclick="toggleMeal('desayuno')" aria-expanded="false">
            <!-- Icono naranja: amanecer -->
            <div class="meal-icon-bubble" style="background:#FFF3E0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/>
                    <line x1="12" y1="2" x2="12" y2="4"/>
                    <line x1="12" y1="20" x2="12" y2="22"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="2" y1="12" x2="4" y2="12"/>
                    <line x1="20" y1="12" x2="22" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
            </div>
            <div class="meal-text">
                <div class="meal-name">Desayuno ✓</div>
                <div class="meal-kcal">450 kcal</div>
            </div>
            <svg id="chevron-desayuno" class="meal-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <div class="meal-content" id="meal-desayuno">
            <div class="meal-divider"></div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">Avena con leche</span>
                <span class="meal-item-meta">1 taza • 250 kcal</span>
            </div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">Fruta mixta</span>
                <span class="meal-item-meta">150g • 90 kcal</span>
            </div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">Jugo de naranja</span>
                <span class="meal-item-meta">200ml • 110 kcal</span>
            </div>
        </div>
    </div>

    <!-- ── COMIDA (expandida) ── -->
    <div class="meal-card">
        <div class="meal-header" id="hdr-comida" onclick="toggleMeal('comida')" aria-expanded="true">
            <!-- Icono verde-amarillo: sol -->
            <div class="meal-icon-bubble" style="background:#F0FFF4;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#11CF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/>
                    <line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
            </div>
            <div class="meal-text">
                <div class="meal-name">Comida ✓</div>
                <div class="meal-kcal">750 kcal</div>
            </div>
            <svg id="chevron-comida" class="meal-chevron up" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <div class="meal-content open" id="meal-comida">
            <div class="meal-divider"></div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">Pechuga de Pollo</span>
                <span class="meal-item-meta">150g • 250 kcal</span>
            </div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">Arroz Integral</span>
                <span class="meal-item-meta">100g • 350 kcal</span>
            </div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">Ensalada Verde</span>
                <span class="meal-item-meta">1 porción • 150 kcal</span>
            </div>
        </div>
    </div>

    <!-- ── CENA (expandida, pendiente) ── -->
    <div class="meal-card">
        <div class="meal-header" id="hdr-cena" onclick="toggleMeal('cena')" aria-expanded="true">
            <!-- Icono morado: luna -->
            <div class="meal-icon-bubble" style="background:#EDE9FE;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </div>
            <div class="meal-text">
                <div class="meal-name">Cena</div>
                <div class="meal-pending-label">Pendiente</div>
            </div>
            <!-- Sin chevron en Cena pendiente -->
        </div>
        <div class="meal-content open" id="meal-cena">
            <div class="meal-divider"></div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">100g Carne asada</span>
            </div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">2 tortillas de maíz</span>
            </div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">Limón y salsa</span>
            </div>
            <div class="meal-confirm-row">
                <button class="btn-confirmar" onclick="confirmarCena(this)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <!-- ── SNACKS (colapsado) ── -->
    <div class="meal-card">
        <div class="meal-header" id="hdr-snacks" onclick="toggleMeal('snacks')" aria-expanded="false">
            <!-- Icono rosa: snack -->
            <div class="meal-icon-bubble" style="background:#FFF0F6;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#EC4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <circle cx="9" cy="10" r="1" fill="#EC4899"/>
                    <circle cx="14" cy="9" r="1" fill="#EC4899"/>
                    <circle cx="10" cy="14" r="1" fill="#EC4899"/>
                    <circle cx="15" cy="14" r="1" fill="#EC4899"/>
                </svg>
            </div>
            <div class="meal-text">
                <div class="meal-name">Snacks</div>
                <div class="meal-kcal">120 kcal</div>
            </div>
            <svg id="chevron-snacks" class="meal-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
        <div class="meal-content" id="meal-snacks">
            <div class="meal-divider"></div>
            <div class="meal-item">
                <div class="meal-item-dot"></div>
                <span class="meal-item-name">Almendras naturales</span>
                <span class="meal-item-meta">20g • 120 kcal</span>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

</div><!-- /mobile-container -->

<script>
    function toggleMeal(id) {
        const content  = document.getElementById('meal-' + id);
        const chevron  = document.getElementById('chevron-' + id);
        const header   = document.getElementById('hdr-' + id);

        if (!content) return;

        const isOpen = content.classList.toggle('open');
        if (chevron) chevron.classList.toggle('up', isOpen);
        if (header)  header.setAttribute('aria-expanded', isOpen);
    }

    function confirmarCena(btn) {
        btn.disabled = true;
        btn.textContent = '✓ Confirmado';
        btn.style.background = '#6B7280';
    }
</script>

</body>
</html>
