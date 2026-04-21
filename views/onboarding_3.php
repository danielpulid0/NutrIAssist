<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.html");
    exit();
}
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
    <title>NutrIAssist - Nivel de Actividad</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        .mobile-container { background-color: #FAFBFA; }
    </style>
</head>
<body>

    <div class="mobile-container">

        <div class="nav-bar">
            <a href="onboarding_2.php" class="back-btn" aria-label="Volver">&#8592;</a>
            <div class="progress-segments">
                <div class="progress-seg active"></div>
                <div class="progress-seg active"></div>
                <div class="progress-seg active"></div>
            </div>
        </div>

        <!-- Ícono héroe: persona corriendo -->
        <div class="icon-circle">
            <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="26" cy="26" r="24" stroke="#11CF50" stroke-width="2" stroke-dasharray="4 4" opacity="0.5"/>
                <path d="M8 26H18L22 14L30 38L34 26H44" stroke="#11CF50" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="screen-header">
            <h1>¿Cuál es tu nivel de actividad?</h1>
            <p>Esto nos ayuda a calcular tus necesidades calóricas diarias con precisión.</p>
        </div>

        <form action="../controllers/procesar_onboarding.php" method="POST" class="flex-form-container">

            <!-- Sedentario -->
            <input type="radio" id="act_sedentario" name="actividad" value="1.2" class="radio-hidden" required>
            <label for="act_sedentario" class="option-card">
                <div class="option-icon-box">
                    <!-- Barra 1 de 4 llena -->
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2"  y="18" width="4" height="4" rx="1" fill="#11CF50"/>
                        <rect x="8"  y="14" width="4" height="8" rx="1" fill="#D1D5DB"/>
                        <rect x="14" y="10" width="4" height="12" rx="1" fill="#D1D5DB"/>
                        <rect x="20" y="6"  width="4" height="16" rx="1" fill="#D1D5DB"/>
                    </svg>
                </div>
                <div class="option-text">
                    <h3>Sedentario</h3>
                    <p>Poco o nada de ejercicio</p>
                </div>
                <div class="option-indicator"></div>
            </label>

            <!-- Ligeramente Activo -->
            <input type="radio" id="act_ligero" name="actividad" value="1.375" class="radio-hidden">
            <label for="act_ligero" class="option-card">
                <div class="option-icon-box">
                    <!-- Barras 1–2 llenas -->
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2"  y="18" width="4" height="4"  rx="1" fill="#11CF50"/>
                        <rect x="8"  y="14" width="4" height="8"  rx="1" fill="#11CF50"/>
                        <rect x="14" y="10" width="4" height="12" rx="1" fill="#D1D5DB"/>
                        <rect x="20" y="6"  width="4" height="16" rx="1" fill="#D1D5DB"/>
                    </svg>
                </div>
                <div class="option-text">
                    <h3>Ligeramente Activo</h3>
                    <p>Ejercicio 2 a 3 días por semana</p>
                </div>
                <div class="option-indicator"></div>
            </label>

            <!-- Moderadamente Activo -->
            <input type="radio" id="act_moderado" name="actividad" value="1.55" class="radio-hidden">
            <label for="act_moderado" class="option-card">
                <div class="option-icon-box">
                    <!-- Barras 1–3 llenas -->
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2"  y="18" width="4" height="4"  rx="1" fill="#11CF50"/>
                        <rect x="8"  y="14" width="4" height="8"  rx="1" fill="#11CF50"/>
                        <rect x="14" y="10" width="4" height="12" rx="1" fill="#11CF50"/>
                        <rect x="20" y="6"  width="4" height="16" rx="1" fill="#D1D5DB"/>
                    </svg>
                </div>
                <div class="option-text">
                    <h3>Moderadamente Activo</h3>
                    <p>Ejercicio 4 a 5 días por semana</p>
                </div>
                <div class="option-indicator"></div>
            </label>

            <!-- Muy Activo -->
            <input type="radio" id="act_muy_activo" name="actividad" value="1.725" class="radio-hidden">
            <label for="act_muy_activo" class="option-card">
                <div class="option-icon-box">
                    <!-- Todas las barras llenas -->
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2"  y="18" width="4" height="4"  rx="1" fill="#11CF50"/>
                        <rect x="8"  y="14" width="4" height="8"  rx="1" fill="#11CF50"/>
                        <rect x="14" y="10" width="4" height="12" rx="1" fill="#11CF50"/>
                        <rect x="20" y="6"  width="4" height="16" rx="1" fill="#11CF50"/>
                    </svg>
                </div>
                <div class="option-text">
                    <h3>Muy Activo</h3>
                    <p>Ejercicio diario intenso</p>
                </div>
                <div class="option-indicator"></div>
            </label>

            <div class="sticky-footer">
                <button type="submit" class="btn-primary">Comenzar mi plan</button>
            </div>
        </form>
    </div>

</body>
</html>