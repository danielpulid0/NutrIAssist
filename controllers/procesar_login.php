<?php
require_once '../utils/Auth.php';
Auth::initSession();
Auth::requirePost();

require_once '../config/conexion.php';
    
    // 1. Limpiar el correo ingresado
    $email = trim($_POST['email']);
    $password_ingresada = $_POST['password'];

    require_once '../models/Usuario.php';

    try {
        // 2. Buscar si existe un usuario con ese correo usando el modelo
        $usuario = Usuario::getByEmail($conn, $email);

        // 4. Validar: ¿Existe el usuario? Y si existe, ¿la contraseña coincide con el Hash?
        if ($usuario && password_verify($password_ingresada, $usuario['password_hash'])) {
            
            // ¡Login Exitoso! Regeneramos el ID de sesión por seguridad (evita robo de sesiones)
            session_regenerate_id(true);

            // Guardamos los datos críticos en la memoria del servidor
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            
            // (Opcional) Aquí podrías volver a calcular la meta de calorías o sacarla de la DB,
            // por ahora ponemos un default para que el Dashboard no marque error.
            $_SESSION['meta_calorias'] = 2000; 

            // Lo enviamos directo al Dashboard
            header("Location: ../views/dashboard.php");
            exit();

        } else {
            // Login Fallido: Regresamos al login con un código de error en la URL
            header("Location: ../views/login.html?error=1");
            exit();
        }

    } catch(PDOException $e) {
        die("Error de sistema: " . $e->getMessage());
    }

?>