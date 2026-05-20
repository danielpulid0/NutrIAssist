<?php
require_once '../utils/Auth.php';
Auth::initSession();
Auth::requirePost();

require_once '../config/conexion.php';
    
    require_once '../utils/Validator.php';
    $validator = new Validator();

    // 1. Limpiar el correo ingresado
    $email = trim($_POST['email'] ?? '');
    $password_ingresada = $_POST['password'] ?? '';

    $validator->required($_POST, ['email', 'password'])->email($email);

    if ($validator->hasErrors()) {
        header("Location: ../views/login.html?error=campos_invalidos");
        exit();
    }

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
            
            // Calcular metas dinámicas del usuario en el login
            $usuarioCompleto = Usuario::getById($conn, $usuario['id_usuario']);
            if ($usuarioCompleto && isset($usuarioCompleto['peso_kg'])) {
                $cumpleanos = new DateTime($usuarioCompleto['fecha_nacimiento']);
                $edad = (new DateTime())->diff($cumpleanos)->y;
                
                $multiplicadores = [1 => 1.200, 2 => 1.375, 3 => 1.550, 4 => 1.725];
                $factor_act = $multiplicadores[$usuarioCompleto['id_nivel_actividad'] ?? 1] ?? 1.200;
                
                $metas = Usuario::calcularMetasNutricionales(
                    $usuarioCompleto['peso_kg'], $usuarioCompleto['altura_cm'],
                    $edad, $usuarioCompleto['sexo'], $factor_act, $usuarioCompleto['meta_principal']
                );
                $_SESSION['meta_calorias'] = $metas['calorias'];
                $_SESSION['meta_proteina'] = $metas['proteinas'];
                $_SESSION['meta_grasas']   = $metas['grasas'];
                $_SESSION['meta_carbs']    = $metas['carbos'];
            } else {
                $_SESSION['meta_calorias'] = 2000; 
                $_SESSION['meta_proteina'] = 150;
                $_SESSION['meta_grasas']   = 70;
                $_SESSION['meta_carbs']    = 220;
            }

            // Lo enviamos directo al Dashboard
            header("Location: ../views/dashboard.php");
            exit();

        } else {
            // Login Fallido: Regresamos al login con un código de error en la URL
            header("Location: ../views/login.html?error=credenciales_invalidas");
            exit();
        }

    } catch(PDOException $e) {
        die("Error de sistema: " . $e->getMessage());
    }

?>