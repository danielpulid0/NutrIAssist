<?php
session_start();

// Validar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.html");
    exit();
}

// Rescatar datos de la memoria del servidor
$nombre_usuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$meta_calorias = $_SESSION['meta_calorias'] ?? 2000; // Valor por defecto si falla algo

// Datos simulados (Hardcoded) por ahora. En la Fase 4 esto vendrá de MySQL (SUM de la tabla Comidas)
$calorias_consumidas = 850; 
$calorias_restantes = $meta_calorias - $calorias_consumidas;

// Matemáticas para el anillo circular (Porcentaje)
$porcentaje_anillo = ($calorias_consumidas / $meta_calorias) * 100;
if ($porcentaje_anillo > 100) $porcentaje_anillo = 100;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Inicio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <style>
        .header-dashboard {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .user-greeting p { color: var(--color-text-gray); font-size: 0.9rem; margin-bottom: 0.2rem; }
        .user-greeting h1 { font-size: 1.5rem; }

        /* EL ANILLO CIRCULAR DE CALORÍAS */
        .ring-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 2rem 0;
        }

        .calorie-ring {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            /* Aquí está la magia: un gradiente cónico que se llena según el porcentaje de PHP */
            background: conic-gradient(
                var(--color-malachite) <?= $porcentaje_anillo ?>%, 
                var(--color-mint) <?= $porcentaje_anillo ?>% 100%
            );
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .ring-inner {
            width: 170px;
            height: 170px;
            background-color: var(--color-bg);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .ring-inner h2 { font-size: 2.2rem; margin: 0; color: var(--color-text-dark); }
        .ring-inner p { font-size: 0.9rem; color: var(--color-text-gray); }

        /* GRID PARA LOS MACRONUTRIENTES */
        .macros-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .macro-card {
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1rem 0.5rem;
            text-align: center;
        }

        .macro-title { font-size: 0.8rem; color: var(--color-text-gray); margin-bottom: 0.25rem; }
        .macro-value { font-size: 1.1rem; font-weight: 600; color: var(--color-text-dark); }
        
        /* Contenedor principal ajustado para el footer */
        .mobile-container {
            padding-bottom: 80px; /* Espacio para que el footer no tape el contenido */
        }
    </style>
</head>
<body>

    <div class="mobile-container">
        
        <div class="header-dashboard">
            <div class="user-greeting">
                <p>Hola de nuevo,</p>
                <h1><?= htmlspecialchars($nombre_usuario) ?></h1>
            </div>
            <div style="font-size: 1.5rem; color: var(--color-text-gray);">🔔</div>
        </div>

        <div class="ring-container">
            <div class="calorie-ring">
                <div class="ring-inner">
                    <h2><?= $calorias_restantes ?></h2>
                    <p>kcal restantes</p>
                </div>
            </div>
        </div>

        <div class="macros-grid">
            <div class="macro-card">
                <div class="macro-title">Proteína</div>
                <div class="macro-value">45g / 120g</div>
            </div>
            <div class="macro-card">
                <div class="macro-title">Carbs</div>
                <div class="macro-value">100g / 250g</div>
            </div>
            <div class="macro-card">
                <div class="macro-title">Grasas</div>
                <div class="macro-value">20g / 65g</div>
            </div>
        </div>

        <div class="card">
            <h3>Consejo del día</h3>
            <p style="margin-top: 0.5rem;">Estás a 400 kcal de tu meta para la cena. Una ensalada con pechuga de pollo sería ideal. ¿Quieres que Gemma te sugiera una receta?</p>
        </div>

        <?php include 'includes/footer.php'; ?>

    </div>

</body>
</html>