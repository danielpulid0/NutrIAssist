<?php
session_start();

$recetas_json = '[
  {
    "id": 1,
    "titulo": "Bowl de Quinoa y Pollo",
    "img": "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=400&fit=crop",
    "tiempo": "30 min",
    "costo_txt": "Medio",
    "kcal": "550 kcal",
    "tag_bg": "var(--color-mint)",
    "tag_text": "var(--color-malachite)",
    "tag_label": "Fácil",
    "ingredientes": [
        "150g Pechuga de Pollo",
        "1 taza Quinoa cocida",
        "1/2 Aguacate",
        "50g Queso Panela"
    ],
    "pasos": [
        "Cocina la quinoa según las instrucciones del paquete. Normalmente es 1 parte de quinoa por 2 de agua, cocida a fuego lento durante 15 minutos.",
        "Sazona la pechuga de pollo con sal y pimienta. Ásala en una sartén a fuego medio hasta que esté dorada y cocida por completo.",
        "Corta el aguacate en rodajas y el queso panela en cubos pequeños.",
        "Sirve la quinoa en un bowl, coloca el pollo encima y decora con el aguacate y el queso. ¡Disfruta!"
    ]
  }
]';

$recetas = json_decode($recetas_json, true);
$receta = $recetas[0]; // Forzar a id 1 para esta demo con backdoor.
if(isset($_GET['id']) && $_GET['id']){
   // Si quisiéramos filtrar haríamos un array_search aquí. Por ahora mock ID 1 siempre carga completo.
   // Las otras tarjetas genéricas en la pantalla anterior no tienen su array de instrucciones.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Detalle de Receta</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        body { background-color: #1a1a1a; } /* El fondo debe ser oscuro atrás del contenedor para simular mobile frame */
        .mobile-container {
            padding: 0;
            background-color: var(--color-bg);
            padding-bottom: 2rem;
            position: relative;
        }

        /* HERO HEADER */
        .hero {
            position: relative;
            width: 100%;
            height: 300px;
        }

        .hero-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60%;
            background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%);
        }

        .back-nav {
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 100%;
            display: flex;
            align-items: center;
            z-index: 10;
        }
        .back-btn-float {
            width: 36px;
            height: 36px;
            background-color: var(--color-bg);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--color-text-dark);
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-title-float {
            flex-grow: 1;
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--color-bg);
            margin-right: calc(1rem + 36px); /* Center exactly */
            text-shadow: 0 1px 3px rgba(0,0,0,0.4);
        }

        .hero-title {
            position: absolute;
            bottom: 1.5rem;
            left: 1.5rem;
            color: var(--color-bg);
            font-size: 1.8rem;
            font-weight: 700;
            line-height: 1.1;
            width: 80%;
            text-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }

        .content-body { padding: 1.5rem; }

        /* STATS CARDS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1rem 0.5rem;
            text-align: center;
        }
        .stat-card svg { margin-bottom: 0.25rem; }
        .stat-card p { font-size: 0.7rem; color: var(--color-text-gray); margin-bottom: 0.1rem; }
        .stat-card h3 { font-size: 0.95rem; font-weight: 700; color: var(--color-text-dark); }

        /* INGREDIENTES */
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--color-text-dark);
        }

        .ing-list {
            display: flex;
            flex-direction: column;
            border: 1px solid var(--color-border);
            border-radius: 14px;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .ing-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--color-border);
            cursor: pointer;
        }
        .ing-item:last-child { border-bottom: none; }

        .ing-cb {
            width: 20px;
            height: 20px;
            margin-right: 1rem;
            accent-color: var(--color-malachite);
        }
        
        .ing-text {
            font-size: 0.95rem;
            color: var(--color-text-dark);
        }

        /* INSTRUCCIONES */
        .inst-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin-bottom: 3rem;
        }

        .inst-item {
            display: flex;
            gap: 1rem;
        }

        .inst-num {
            width: 32px;
            height: 32px;
            background-color: var(--color-mint);
            color: var(--color-malachite);
            font-weight: 700;
            font-size: 1rem;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
        }

        .inst-text {
            font-size: 0.95rem;
            color: var(--color-text-gray);
            line-height: 1.5;
            padding-top: 5px; /* Alignment with circle */
        }

        /* FIXED BUTTON */
        .sticky-action {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 3rem);
            max-width: calc(430px - 3rem);
            z-index: 100;
        }

        .btn-register {
            width: 100%;
            background-color: var(--color-primary);
            color: #1a1a1a;
            font-weight: 700;
            font-size: 1.05rem;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(55, 246, 119, 0.3);
            cursor: pointer;
        }

    </style>
</head>
<body>

    <div class="mobile-container">

        <div class="hero">
            <img class="hero-img" src="<?= $receta['img'] ?>" alt="Hero">
            <div class="hero-overlay"></div>
            
            <div class="back-nav">
                <a href="recetas.php" class="back-btn-float">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <div class="header-title-float">Detalle de Receta</div>
            </div>

            <div class="hero-title"><?= htmlspecialchars($receta['titulo']) ?></div>
        </div>

        <div class="content-body">
            
            <div class="stats-row">
                <div class="stat-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-malachite)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <p>Tiempo</p>
                    <h3><?= htmlspecialchars($receta['tiempo']) ?></h3>
                </div>
                <div class="stat-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-malachite)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    <p>Costo</p>
                    <h3><?= htmlspecialchars($receta['costo_txt']) ?></h3>
                </div>
                <div class="stat-card">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-malachite)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c-2.28 0-3-2-3-3s1.72-3 4-3c2.28 0 4 1.28 4 3 0 1.28-.72 3-3 3a2.5 2.5 0 0 0-2.5 2.5v1.5a4 4 0 0 1-4 4"></path><path d="M17.5 14.5a2.5 2.5 0 0 1-2.5-2.5"></path><path d="M11.5 20.5A4.5 4.5 0 0 1 7 16"></path><path d="M12 2v20"></path></svg>
                    <p>Calorías</p>
                    <h3><?= htmlspecialchars($receta['kcal']) ?></h3>
                </div>
            </div>

            <h2 class="section-title">Ingredientes</h2>
            <div class="ing-list">
                <?php foreach($receta['ingredientes'] as $ing): ?>
                <label class="ing-item">
                    <input type="checkbox" class="ing-cb">
                    <span class="ing-text"><?= htmlspecialchars($ing) ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <h2 class="section-title">Instrucciones</h2>
            <div class="inst-list">
                <?php foreach($receta['pasos'] as $index => $paso): ?>
                <div class="inst-item">
                    <div class="inst-num"><?= $index + 1 ?></div>
                    <div class="inst-text"><?= htmlspecialchars($paso) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="sticky-action">
            <button class="btn-register">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>
                Registrar Comida
            </button>
        </div>

    </div>

</body>
</html>
