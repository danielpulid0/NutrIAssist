<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utils/mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!$email) {
        header("Location: ../views/recuperar.php?error=invalid_email");
        exit();
    }

    try {
        // 1. Verificar si el usuario existe
        $stmt = $conn->prepare("SELECT id_usuario, nombre FROM Usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // 2. Generar Token y Expiración (1 hora)
            $token = bin2hex(random_bytes(32));
            $expiracion = date("Y-m-d H:i:s", strtotime('+1 hour'));

            // 3. Guardar en la base de datos
            $update = $conn->prepare("UPDATE Usuarios SET reset_token = ?, reset_token_expiry = ? WHERE id_usuario = ?");
            $update->execute([$token, $expiracion, $usuario['id_usuario']]);

            // 4. Enviar Correo
            // Ajusta la URL según tu entorno local
            $enlace = "http://localhost/nutriassist/views/restablecer.php?token=" . $token;
            
            if (enviarCorreoRecuperacion($email, $usuario['nombre'], $enlace)) {
                // Éxito
                header("Location: ../views/recuperar.php?enviado=1");
            } else {
                // Error al enviar
                header("Location: ../views/recuperar.php?error=mail_fail");
            }
        } else {
            // Por seguridad, no decimos si el correo existe o no
            header("Location: ../views/recuperar.php?enviado=1");
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        header("Location: ../views/recuperar.php?error=db_error");
    }
} else {
    header("Location: ../views/recuperar.php");
}
exit();
