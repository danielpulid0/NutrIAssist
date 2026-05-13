<?php
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($password) || $password !== $confirm_password) {
        header("Location: ../views/restablecer.php?token=$token&error=mismatch");
        exit();
    }

    try {
        // 1. Verificar token válido
        $stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // 2. Encriptar nueva contraseña
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // 3. Actualizar contraseña y limpiar token
            $update = $conn->prepare("UPDATE Usuarios SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id_usuario = ?");
            $update->execute([$password_hash, $usuario['id_usuario']]);

            // 4. Redirigir al login con éxito
            header("Location: ../views/login.html?reset=success");
        } else {
            header("Location: ../views/recuperar.php?error=expired");
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        header("Location: ../views/restablecer.php?token=$token&error=db_error");
    }
} else {
    header("Location: ../views/login.html");
}
exit();
