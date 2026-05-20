<?php
// 1. Iniciamos la sesión para comprobar si el usuario ya está logueado
session_start();

// 2. Lógica de Enrutamiento: Si ya hay un ID de usuario en la memoria, saltamos la bienvenida
if (isset($_SESSION['usuario_id'])) {
    header("Location: views/dashboard.php");
    exit(); // Siempre pon exit() después de un header para detener la ejecución
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Bienvenido</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/global.css?v=3">
    <link rel="stylesheet" href="assets/css/index.css">
    
    <!-- PWA Config -->
    <link rel="manifest" href="/nutriassist/manifest.json">
    <meta name="theme-color" content="#15B85E">
    <link rel="apple-touch-icon" href="/nutriassist/assets/img/icon-192.png">
</head>
<body>

    <div class="mobile-container">
        
        <div class="splash-container">
            <div class="logo-container">
                <img src="assets/img/icon-192.png" alt="NutrIAssist Logo">
            </div>
            
            <h1>NutrIAssist</h1>
            <p>Tu asistente nutricional inteligente. Registra tus comidas hablando de forma natural con nuestra IA.</p>
        </div>

        <div class="action-buttons">
            <a href="views/registro.html" class="btn-primary btn-primary-link">Crear Cuenta Nueva</a>
            <a href="views/login.html" class="btn-secondary">Ya tengo cuenta</a>
        </div>

        <div class="splash-footer">
            © Derechos reservados Galatics Software Association
        </div>

    </div>

    <!-- Registro de Service Worker para PWA -->
    <script>
        // Guardar la zona horaria del usuario para el backend (PHP)
        document.cookie = "user_timezone=" + Intl.DateTimeFormat().resolvedOptions().timeZone + "; path=/; max-age=31536000";

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/nutriassist/sw.js')
                    .then(reg => console.log('Service Worker registrado!', reg.scope))
                    .catch(err => console.log('Error registrando Service Worker:', err));
            });
        }
    </script>
</body>
</html>