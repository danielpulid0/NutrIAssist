<?php
session_start();
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
        .mobile-container { background-color: #FAFBFA; }
    </style>
</head>
<body>

    <div class="mobile-container">
        <!-- Barra de navegación: sin atrás en el primer paso -->
        <div class="nav-bar">
            <div class="progress-segments">
                <div class="progress-seg active"></div>
                <div class="progress-seg"></div>
                <div class="progress-seg"></div>
            </div>
        </div>

        <!-- Ícono héroe: target/bullseye en SVG verde -->
        <div class="icon-circle">
            <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="26" cy="26" r="20" stroke="#11CF50" stroke-width="3"/>
                <circle cx="26" cy="26" r="12" stroke="#11CF50" stroke-width="3"/>
                <circle cx="26" cy="26" r="4"  fill="#11CF50"/>
            </svg>
        </div>

        <div class="screen-header">
            <h1>¿Cuál es tu objetivo?</h1>
            <p>Calcularemos tus calorías necesarias para lograrlo</p>
        </div>

        <form action="onboarding_2.php" method="POST" class="flex-form-container">

            <!-- Perder Grasa -->
            <input type="radio" id="goal_lose" name="objetivo" value="perder_grasa" class="radio-hidden" required>
            <label for="goal_lose" class="option-card">
                <div class="option-icon-box">
                    <!-- flame icon -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2c0 6-6 8-6 14a6 6 0 0012 0c0-6-6-8-6-14z"/>
                        <path d="M12 12c0 3-2 4-2 7a2 2 0 004 0c0-3-2-4-2-7z"/>
                    </svg>
                </div>
                <div class="option-text">
                    <h3>Perder Grasa</h3>
                    <p>Optimiza la pérdida de peso</p>
                </div>
                <div class="option-indicator"></div>
            </label>

            <!-- Ganar Músculo -->
            <input type="radio" id="goal_gain" name="objetivo" value="ganar_musculo" class="radio-hidden">
            <label for="goal_gain" class="option-card">
                <div class="option-icon-box">
                    <!-- trending-up icon -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                </div>
                <div class="option-text">
                    <h3>Ganar Músculo</h3>
                    <p>Incrementa tu peso y fuerza</p>
                </div>
                <div class="option-indicator"></div>
            </label>

            <!-- Mantener Peso -->
            <input type="radio" id="goal_maintain" name="objetivo" value="mantener_peso" class="radio-hidden">
            <label for="goal_maintain" class="option-card">
                <div class="option-icon-box">
                    <!-- scale/balance icon -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="3" x2="12" y2="21"/>
                        <path d="M5 6l7-3 7 3"/>
                        <path d="M5 12l-3 6h6l-3-6z"/>
                        <path d="M19 12l-3 6h6l-3-6z"/>
                    </svg>
                </div>
                <div class="option-text">
                    <h3>Mantener Peso</h3>
                    <p>Busca la recomposición corporal</p>
                </div>
                <div class="option-indicator"></div>
            </label>

            <div class="sticky-footer">
                <button type="submit" class="btn-primary">Continuar</button>
            </div>
        </form>
    </div>

</body>
</html>