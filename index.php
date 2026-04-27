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
    
    <!-- PWA Config -->
    <link rel="manifest" href="/nutriassist/manifest.json">
    <meta name="theme-color" content="#15B85E">
    <link rel="apple-touch-icon" href="/nutriassist/assets/img/icon-192.png">
    
    <style>
        /* Estilos exclusivos para la pantalla de bienvenida */
        .splash-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            height: 100%;
            padding: 2rem;
            flex-grow: 1;
        }

        .logo-container {
            width: 120px;
            height: 120px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 28px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .splash-container h1 {
            font-size: 2.2rem;
            color: var(--color-text-dark);
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .splash-container p {
            font-size: 1.05rem;
            color: var(--color-text-gray);
            margin-bottom: 3rem;
            line-height: 1.5;
        }

        .action-buttons {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: auto; /* Empuja los botones hacia abajo */
            padding-bottom: 2rem;
        }

        /* Botón secundario (Transparente con borde) */
        .btn-secondary {
            width: 100%;
            background-color: transparent;
            color: var(--color-malachite);
            font-weight: 600;
            font-size: 1.05rem;
            padding: 1rem;
            border: 2px solid var(--color-malachite);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary:active {
            background-color: var(--color-mint);
        }

        /* Ajuste para que los botones link se comporten como el botón principal */
        .btn-primary-link {
            text-decoration: none;
            display: inline-block;
        }
    </style>
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

    </div>

    <!-- Registro de Service Worker para PWA -->
    <script>
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