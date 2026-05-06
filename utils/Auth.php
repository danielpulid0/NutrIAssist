<?php
class Auth {
    /**
     * Inicia la sesión de forma segura si no está iniciada.
     */
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Valida que un usuario haya iniciado sesión.
     * @param bool $is_api Define el comportamiento de rechazo (Redirección HTML vs Respuesta JSON)
     * @return int Devuelve el ID del usuario si está logueado
     */
    public static function requireLogin($is_api = false) {
        self::initSession();

        if (!isset($_SESSION['usuario_id'])) {
            if ($is_api) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Acceso denegado o sesión expirada']);
                exit();
            } else {
                header("Location: ../index.php"); // o login.html
                exit();
            }
        }

        return (int) $_SESSION['usuario_id'];
    }

    /**
     * Valida que la petición sea estrictamente método POST (útil para APIs)
     */
    public static function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Se requiere POST.']);
            exit();
        }
    }
}
?>
