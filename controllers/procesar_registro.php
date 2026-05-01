<?php
// Iniciar la sesión para recordar al usuario una vez registrado
session_start();

// 1. Conectar a la base de datos (subimos un nivel de carpeta con '../')
require_once '../config/conexion.php';

// 2. Verificar que los datos llegaron por el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
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

    // 4.5 VALIDACIÓN PREVIA: Comprobar que el correo no exista ya
    $stmt_check = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE email = :email");
    $stmt_check->bindParam(':email', $email);
    $stmt_check->execute();
    if ($stmt_check->rowCount() > 0) {
        // Enviar al usuario de vuelta a registro.html con un mensaje de error
        header("Location: ../views/registro.html?error=email_existente");
        exit();
    }

    // Valores temporales para los campos obligatorios (se llenarán en el Onboarding)
    $fecha_temp = '2000-01-01'; 
    $peso_temp = 0.00;
    $altura_temp = 0;

    try {
        // 5. Preparar la consulta SQL (Evita Inyección SQL)
        $sql = "INSERT INTO Usuarios (email, password_hash, nombre, fecha_nacimiento, peso_kg, altura_cm) 
                VALUES (:email, :password_hash, :nombre, :fecha, :peso, :altura)";
        
        $stmt = $conn->prepare($sql);
        
        // 6. Vincular las variables a la consulta
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':fecha', $fecha_temp);
        $stmt->bindParam(':peso', $peso_temp);
        $stmt->bindParam(':altura', $altura_temp);
        
        // 7. Ejecutar la inserción
        $stmt->execute();

        // 8. Obtener el ID que MySQL le asignó a este nuevo usuario
        $id_nuevo_usuario = $conn->lastInsertId();

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
} else {
    // Si alguien intenta entrar a este archivo directamente por la URL, lo regresamos
    header("Location: ../views/registro.html");
    exit();
}
?>