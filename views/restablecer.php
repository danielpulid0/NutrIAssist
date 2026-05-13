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
                <div class="input-wrapper">
                    <input class="form-input" type="password" id="password" name="password" placeholder="·········" required minlength="6">
                    <button type="button" class="input-icon-right toggle-pw" data-target="password" aria-label="Mostrar contraseña">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>

                <label class="form-label" for="confirm_password">Confirmar Contraseña</label>
                <div class="input-wrapper">
                    <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="·········" required minlength="6">
                    <button type="button" class="input-icon-right toggle-pw" data-target="confirm_password" aria-label="Mostrar contraseña">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>

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

    <script>
        // Seleccionamos todos los botones que alternan contraseñas
        const toggleButtons = document.querySelectorAll('.toggle-pw');

        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Obtenemos el ID del input correspondiente usando el atributo data-target
                const targetId = button.getAttribute('data-target');
                const pwInput = document.getElementById(targetId);
                
                const isHidden = pwInput.type === 'password';
                pwInput.type = isHidden ? 'text' : 'password';
                
                // Cambiamos el ícono dependiendo del estado
                button.innerHTML = isHidden
                    ? `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
                    : `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
            });
        });
    </script>
</body>

</html>
