<?php
// 1. Reanudamos la sesión para saber quién está haciendo el onboarding
session_start();

// Si un intruso intenta entrar aquí sin haberse registrado primero, lo regresamos al registro
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Tu Objetivo</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <style>
        /* Estilos específicos para esta pantalla */
        .progress-bar {
            width: 100%;
            height: 6px;
            background-color: var(--color-border);
            border-radius: 4px;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .progress-fill {
            width: 33.33%; /* Paso 1 de 3 */
            height: 100%;
            background-color: var(--color-primary);
            border-radius: 4px;
        }

        .header { text-align: center; margin-bottom: 2rem; }
        
        /* Ocultamos el circulito feo del radio button */
        .radio-hidden {
            display: none;
        }

        /* Convertimos el label en una tarjeta interactiva */
        .goal-card {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            border: 2px solid var(--color-border);
            border-radius: 16px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: var(--color-bg);
        }

        .goal-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            background-color: var(--color-bg-app);
            width: 48px;
            height: 48px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 12px;
        }

        .goal-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--color-text-dark);
            margin-bottom: 0.2rem;
        }

        .goal-info p {
            font-size: 0.85rem;
            color: var(--color-text-gray);
        }

        /* EL TRUCO DE MAGIA EN CSS: 
           Si el input oculto está seleccionado (:checked), 
           cambiamos el estilo de la tarjeta que le sigue (+) */
        .radio-hidden:checked + .goal-card {
            border-color: var(--color-malachite);
            background-color: var(--color-mint);
        }

        .radio-hidden:checked + .goal-card .goal-icon {
            background-color: white;
        }

        .sticky-footer {
            margin-top: auto; /* Empuja el botón al fondo de la pantalla */
            padding-top: 2rem;
        }
    </style>
</head>
<body>

    <div class="mobile-container">
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>

        <div class="header">
            <h1>¿Cuál es tu objetivo?</h1>
            <p>Adaptaremos tu plan nutricional para que lo logres de forma saludable.</p>
        </div>

        <form action="onboarding_2.php" method="POST" style="display: flex; flex-direction: column; flex-grow: 1;">
            
            <input type="radio" id="goal_lose" name="objetivo" value="perder_grasa" class="radio-hidden" required>
            <label for="goal_lose" class="goal-card">
                <div class="goal-icon">🔥</div>
                <div class="goal-info">
                    <h3>Perder Grasa</h3>
                    <p>Optimiza la pérdida de peso de forma sostenible.</p>
                </div>
            </label>

            <input type="radio" id="goal_maintain" name="objetivo" value="mantener_peso" class="radio-hidden">
            <label for="goal_maintain" class="goal-card">
                <div class="goal-icon">⚖️</div>
                <div class="goal-info">
                    <h3>Mantener Peso</h3>
                    <p>Busca la recomposición y el equilibrio corporal.</p>
                </div>
            </label>

            <input type="radio" id="goal_gain" name="objetivo" value="ganar_musculo" class="radio-hidden">
            <label for="goal_gain" class="goal-card">
                <div class="goal-icon">💪</div>
                <div class="goal-info">
                    <h3>Ganar Músculo</h3>
                    <p>Incrementa tu peso y fuerza gradualmente.</p>
                </div>
            </label>

            <div class="sticky-footer">
                <button type="submit" class="btn-primary">Continuar</button>
            </div>
        </form>
    </div>

</body>
</html>