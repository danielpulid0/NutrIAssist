<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Recuperar Contraseña</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css?v=3">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

    <div class="mobile-container">
        <!-- Logo + marca -->
        <div class="brand-logo">
            <div class="icon-circle">
                <img src="../assets/img/icon-192.png" alt="NutrIAssist logo">
            </div>
            <div class="brand-name">NutrIAssist</div>
        </div>

        <div class="section-title">Recuperar Contraseña</div>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">
            Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
        </p>

        <?php if (isset($_GET['enviado'])): ?>
            <div class="alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                Si el correo está registrado, recibirás un enlace en unos momentos.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert-error" style="display: block; margin-bottom: 20px;">
                Ocurrió un error al procesar tu solicitud. Inténtalo de nuevo.
            </div>
        <?php endif; ?>

        <form action="../controllers/solicitar_recuperacion.php" method="POST" class="flex-form-container">
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-input" type="email" id="email" name="email" placeholder="ejemplo@correo.com" required autocomplete="email">

            <div class="sticky-footer">
                <button type="submit" class="btn-primary">Enviar enlace</button>
                <div class="bottom-link">
                    <a href="login.html">Volver al inicio de sesión</a>
                </div>
            </div>
        </form>
    </div>

</body>

</html>
