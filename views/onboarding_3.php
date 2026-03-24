<?php
session_start();

// Validar intrusos
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.html");
    exit();
}

// Atrapar los datos de la Pantalla 2 y guardarlos en la sesión
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edad'])) {
    $_SESSION['onboarding_sexo']   = $_POST['sexo'];
    $_SESSION['onboarding_edad']   = $_POST['edad'];
    $_SESSION['onboarding_altura'] = $_POST['altura'];
    $_SESSION['onboarding_peso']   = $_POST['peso'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Actividad</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <style>
        .progress-bar { width: 100%; height: 6px; background-color: var(--color-border); border-radius: 4px; margin-bottom: 2rem; overflow: hidden; }
        /* Barra al 100% (Paso 3 de 3) */
        .progress-fill { width: 100%; height: 100%; background-color: var(--color-primary); border-radius: 4px; transition: width 0.3s ease; }
        
        .header { text-align: center; margin-bottom: 2rem; }
        
        .radio-hidden { display: none; }
        
        .activity-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid var(--color-border);
            border-radius: 12px;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background-color: var(--color-bg);
        }

        .activity-info h3 { font-size: 1rem; font-weight: 600; color: var(--color-text-dark); margin-bottom: 0.1rem; }
        .activity-info p { font-size: 0.8rem; color: var(--color-text-gray); }

        .radio-hidden:checked + .activity-card {
            border-color: var(--color-malachite);
            background-color: var(--color-mint);
        }

        .sticky-footer { margin-top: auto; padding-top: 1.5rem; }
    </style>
</head>
<body>

    <div class="mobile-container">
        <div class="progress-bar"><div class="progress-fill"></div></div>

        <div class="header">
            <h1>Nivel de actividad</h1>
            <p>¿Qué tan activo eres en tu día a día?</p>
        </div>

        <form action="../controllers/procesar_onboarding.php" method="POST" style="display: flex; flex-direction: column; flex-grow: 1;">
            
            <input type="radio" id="act_sedentario" name="actividad" value="1.2" class="radio-hidden" required>
            <label for="act_sedentario" class="activity-card">
                <div class="activity-info">
                    <h3>Sedentario</h3>
                    <p>Trabajo de oficina, poco o nada de ejercicio.</p>
                </div>
            </label>

            <input type="radio" id="act_ligero" name="actividad" value="1.375" class="radio-hidden">
            <label for="act_ligero" class="activity-card">
                <div class="activity-info">
                    <h3>Ligeramente Activo</h3>
                    <p>Ejercicio ligero o deportes 1-3 días a la semana.</p>
                </div>
            </label>

            <input type="radio" id="act_moderado" name="actividad" value="1.55" class="radio-hidden">
            <label for="act_moderado" class="activity-card">
                <div class="activity-info">
                    <h3>Moderadamente Activo</h3>
                    <p>Ejercicio moderado o deportes 3-5 días a la semana.</p>
                </div>
            </label>

            <input type="radio" id="act_muy_activo" name="actividad" value="1.725" class="radio-hidden">
            <label for="act_muy_activo" class="activity-card">
                <div class="activity-info">
                    <h3>Muy Activo</h3>
                    <p>Ejercicio fuerte o deportes 6-7 días a la semana.</p>
                </div>
            </label>

            <div class="sticky-footer">
                <button type="submit" class="btn-primary">Finalizar y Calcular Mi Plan</button>
            </div>
        </form>
    </div>

</body>
</html>