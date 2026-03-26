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
  },
  {
    "id": 2,
    "titulo": "Ensalada de Quinoa",
    "img": "https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=400&h=400&fit=crop",
    "tiempo": "10 min",
    "costo_txt": "Medio",
    "kcal": "320 kcal",
    "tag_bg": "#DBEAFE",
    "tag_text": "#2563EB",
    "tag_label": "Vegetariano"
  },
  {
    "id": 3,
    "titulo": "Salmón al Horno",
    "img": "https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=400&h=400&fit=crop",
    "tiempo": "25 min",
    "costo_txt": "Caro",
    "kcal": "450 kcal",
    "tag_bg": "#FEF3C7",
    "tag_text": "#D97706",
    "tag_label": "+Proteína"
  },
  {
    "id": 4,
    "titulo": "Smoothie Verde",
    "img": "https://images.unsplash.com/photo-1610832958506-aa56368176cf?w=400&h=400&fit=crop",
    "tiempo": "5 min",
    "costo_txt": "Económico",
    "kcal": "180 kcal",
    "tag_bg": "#F3E8FF",
    "tag_text": "#9333EA",
    "tag_label": "Bebida"
  },
  {
    "id": 5,
    "titulo": "Ensalada de Aguacate",
    "img": "https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=400&fit=crop",
    "tiempo": "15 min",
    "costo_txt": "Medio",
    "kcal": "380 kcal",
    "tag_bg": "var(--color-mint)",
    "tag_text": "var(--color-malachite)",
    "tag_label": "Keto"
  },
  {
    "id": 6,
    "titulo": "Avena con Frutos",
    "img": "https://images.unsplash.com/photo-1517673132405-a56a62b18caf?w=400&h=400&fit=crop",
    "tiempo": "10 min",
    "costo_txt": "Económico",
    "kcal": "290 kcal",
    "tag_bg": "#FFEDD5",
    "tag_text": "#EA580C",
    "tag_label": "Fibra"
  }
]';

$recetas = json_decode($recetas_json, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Recetas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        .header-recetas {
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-text-dark);
            margin-bottom: 1rem;
        }

        .search-bar {
            width: 100%;
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 99px;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            font-size: 0.95rem;
            outline: none;
            background-image: url('data:image/svg+xml;utf8,<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="%236B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
            background-repeat: no-repeat;
            background-position: 12px center;
            margin-bottom: 1rem;
        }
        .search-bar::placeholder { color: var(--color-text-gray); }

        .chips-container {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            scrollbar-width: none;
        }
        .chips-container::-webkit-scrollbar { display: none; }

        .chip {
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            padding: 0.4rem 0.8rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--color-text-dark);
            white-space: nowrap;
        }

        .recetas-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding-bottom: 80px; /* footer offset */
        }

        .receta-card {
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none; /* Make card clickable link */
            color: inherit;
        }

        .receta-img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
        }

        .receta-info {
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .receta-title {
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .receta-meta {
            font-size: 0.7rem;
            color: var(--color-text-gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .receta-meta svg { width: 12px; height: 12px; }

        .receta-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .receta-kcal {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--color-malachite);
        }

        .receta-tag {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 99px;
        }
        
        .mobile-container {
            background-color: var(--color-bg-app); /* This page has gray bg in image */
        }
    </style>
</head>
<body>

    <div class="mobile-container">
        
        <div class="header-recetas">
            Recetas
        </div>

        <input type="text" class="search-bar" placeholder="Buscar ...">

        <div class="chips-container">
            <div class="chip">$$$: Medio</div>
            <div class="chip">Tiempo: <15 min</div>
            <div class="chip">+ Proteína</div>
        </div>

        <div class="recetas-grid">
            <?php foreach($recetas as $r): ?>
                <a href="receta_detalle.php?id=<?= $r['id'] ?>" class="receta-card">
                    <img src="<?= $r['img'] ?>" alt="Receta" class="receta-img">
                    <div class="receta-info">
                        <div class="receta-title"><?= htmlspecialchars($r['titulo']) ?></div>
                        <div class="receta-meta">
                            <span style="display:flex; align-items:center; gap:2px">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <?= $r['tiempo'] ?>
                            </span>
                            <span style="display:flex; align-items:center; gap:2px">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle></svg>
                                <?= $r['costo_txt'] ?>
                            </span>
                        </div>
                        <div class="receta-footer">
                            <div class="receta-kcal"><?= $r['kcal'] ?></div>
                            <div class="receta-tag" style="background-color: <?= $r['tag_bg'] ?>; color: <?= $r['tag_text'] ?>;">
                                <?= $r['tag_label'] ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php include 'includes/footer.php'; ?>

    </div>

</body>
</html>
