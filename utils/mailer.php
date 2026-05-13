<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carga manual de PHPMailer (Sin Composer)
require __DIR__ . '/../libs/PHPMailer/Exception.php';
require __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require __DIR__ . '/../libs/PHPMailer/SMTP.php';

/**
 * Función para enviar correos electrónicos usando Gmail SMTP
 */
function enviarCorreoRecuperacion($destinatario, $nombreUsuario, $enlace)
{
    $mail = new PHPMailer(true);

    try {
        // Configuración del Servidor
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'soporte.nutriassist@gmail.com'; // <-- REEMPLAZAR
        $mail->Password = 'pgdq ltkf jljy lupy'; // <-- REEMPLAZAR
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Receptores
        $mail->setFrom('soporte.nutriassist@gmail.com', 'NutrIAssist Soporte');
        $mail->addAddress($destinatario, $nombreUsuario);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de Contraseña - NutrIAssist';

        // Cuerpo del correo con diseño básico
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; padding: 20px;'>
                <h2 style='color: #15B85E;'>NutrIAssist</h2>
                <p>Hola, <strong>$nombreUsuario</strong>.</p>
                <p>Hemos recibido una solicitud para restablecer tu contraseña. Si no fuiste tú, ignora este mensaje.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$enlace' style='background-color: #15B85E; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Restablecer Contraseña</a>
                </div>
                <p>Este enlace expirará en 1 hora.</p>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #888;'>Este es un correo automático, por favor no respondas.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}
