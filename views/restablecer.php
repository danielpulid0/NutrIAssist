<?php
require_once __DIR__ . '/../config/conexion.php';

$token = $_GET['token'] ?? '';
$valido = false;

if ($token) {
    try {
        $stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$token]);
        if ($stmt->fetch()) {
            $valido = true;
        }
    } catch (Exception $e) {
        $valido = false;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Nueva Contraseña</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css?v=3">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>

    <div class="mobile-container">
        <div class="brand-logo">
            <div class="icon-circle">
                <img src="../assets/img/icon-192.png" alt="NutrIAssist logo">
            </div>
            <div class="brand-name">NutrIAssist</div>
        </div>

        <?php if ($valido): ?>
            <div class="section-title">Nueva Contraseña</div>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">
                Ingresa tu nueva contraseña a continuación.
            </p>

            <form action="../controllers/procesar_restablecimiento.php" method="POST" class="flex-form-container">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label class="form-label" for="password">Nueva Contraseña</label>
                <input class="form-input" type="password" id="password" name="password" placeholder="·········" required minlength="6">

                <label class="form-label" for="confirm_password">Confirmar Contraseña</label>
                <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="·········" required minlength="6">

                <div class="sticky-footer">
                    <button type="submit" class="btn-primary">Cambiar contraseña</button>
                </div>
            </form>
        <?php else: ?>
            <div class="section-title">Enlace no válido</div>
            <div class="alert-error" style="display: block; margin-bottom: 20px; text-align: center;">
                El enlace de recuperación es inválido o ha expirado.
            </div>
            <div class="sticky-footer">
                <a href="recuperar.php" class="btn-primary" style="text-decoration: none; display: block; text-align: center;">Solicitar nuevo enlace</a>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>
