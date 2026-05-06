<?php
require_once '../utils/Auth.php';
Auth::initSession();
Auth::requirePost();

// 1. Conectar a la base de datos (subimos un nivel de carpeta con '../')
require_once '../config/conexion.php';
    
    // 3. Limpiar y capturar los datos del formulario
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password_plana = $_POST['password'];

    // 3.5 VALIDACIÓN DE CONTRASEÑA
    if (strlen($password_plana) < 8 || 
        !preg_match('/[A-Za-z]/', $password_plana) || 
        !preg_match('/[0-9]/', $password_plana) || 
        !preg_match('/[^A-Za-z0-9]/', $password_plana)) {
        header("Location: ../views/registro.html?error=password_insegura");
        exit();
    }

    // 4. SEGURIDAD: Encriptar la contraseña (¡NUNCA se guarda en texto plano!)
    $password_hash = password_hash($password_plana, PASSWORD_DEFAULT);

    require_once '../models/Usuario.php';

    // 4.5 VALIDACIÓN PREVIA: Comprobar que el correo no exista ya usando el modelo
    if (Usuario::getByEmail($conn, $email) !== false) {
        // Enviar al usuario de vuelta a registro.html con un mensaje de error
        header("Location: ../views/registro.html?error=email_existente");
        exit();
    }

    // Valores temporales para los campos obligatorios (se llenarán en el Onboarding)
    $fecha_temp = '2000-01-01'; 
    $peso_temp = 0.00;
    $altura_temp = 0;

    try {
        // 5. Crear el usuario a través del modelo
        $datos_usuario = [
            'email' => $email,
            'password_hash' => $password_hash,
            'nombre' => $nombre,
            'fecha_nacimiento' => $fecha_temp,
            'peso_kg' => $peso_temp,
            'altura_cm' => $altura_temp
        ];

        $id_nuevo_usuario = Usuario::create($conn, $datos_usuario);
        
        if (!$id_nuevo_usuario) {
            throw new Exception("No se pudo insertar el usuario.");
        }

        // 9. Guardar el ID en la "Memoria" del servidor (Sesión)
        $_SESSION['usuario_id'] = $id_nuevo_usuario;
        $_SESSION['usuario_nombre'] = $nombre;

        // 10. Redirigir al usuario al paso 1 del Onboarding (Objetivos)
        header("Location: ../views/onboarding_1.php");
        exit();

    } catch(PDOException $e) {
        // En caso excepcional que pase la validación previa pero MySQL siga bloqueando
        if ($e->getCode() == 23000) {
            header("Location: ../views/registro.html?error=email_existente");
            exit();
        } else {
            die("Error crítico al registrar: " . $e->getMessage());
        }
    }
?>