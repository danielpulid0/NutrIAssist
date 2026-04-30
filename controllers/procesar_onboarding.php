<?php
session_start();
require_once '../config/conexion.php';

// Si no hay sesión o no llegó el POST, adiós
if (!isset($_SESSION['usuario_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: ../views/registro.html");
    exit();
}

// 1. Recopilar todos los datos de la sesión y el último POST
$id_usuario = $_SESSION['usuario_id'];
$objetivo   = $_SESSION['onboarding_objetivo']; // 'perder_grasa', 'mantener_peso', 'ganar_musculo'
$sexo       = $_SESSION['onboarding_sexo'];     // 'M' o 'F'
$edad       = (int)$_SESSION['onboarding_edad'];
$altura     = (float)$_SESSION['onboarding_altura'];
$peso       = (float)$_SESSION['onboarding_peso'];
$actividad  = (float)$_POST['actividad'];       // Ej. 1.2, 1.55

// 2. FÓRMULA DE MIFFLIN-ST JEOR (Tasa Metabólica Basal - TMB)
if ($sexo === 'M') {
    $tmb = (10 * $peso) + (6.25 * $altura) - (5 * $edad) + 5;
} else {
    $tmb = (10 * $peso) + (6.25 * $altura) - (5 * $edad) - 161;
}

// 3. Gasto Energético Total Diario (TDEE)
$calorias_mantenimiento = $tmb * $actividad;

// 4. Ajuste por Objetivo (Déficit o Superávit Calórico)
$calorias_objetivo = $calorias_mantenimiento;
if ($objetivo === 'perder_grasa') {
    $calorias_objetivo -= 500; // Déficit agresivo pero sano
} elseif ($objetivo === 'ganar_musculo') {
    $calorias_objetivo += 300; // Superávit ligero
}

$calorias_finales = round($calorias_objetivo);

// IMPORTANTE: Calcula la fecha de nacimiento restando la edad al año actual 
// (Como es un MVP, es una aproximación para cumplir con la tabla de MySQL)
$anio_nacimiento = date("Y") - $edad;
$fecha_nacimiento = "$anio_nacimiento-01-01"; 

try {
    // 5. Actualizar la base de datos (El UPDATE en lugar del INSERT)
    // Nota: Como simplificamos el modelo, guardaremos temporalmente el nivel de actividad en otro campo 
    // o simplemente actualizamos los biométricos.
    $sql = "UPDATE Usuarios 
            SET fecha_nacimiento = :fecha, 
                peso_kg = :peso, 
                altura_cm = :altura 
            WHERE id_usuario = :id";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':fecha', $fecha_nacimiento);
    $stmt->bindParam(':peso', $peso);
    $stmt->bindParam(':altura', $altura);
    $stmt->bindParam(':id', $id_usuario);
    $stmt->execute();

    // 6. Limpiar la memoria (buenas prácticas de seguridad)
    unset($_SESSION['onboarding_objetivo']);
    unset($_SESSION['onboarding_sexo']);
    unset($_SESSION['onboarding_edad']);
    unset($_SESSION['onboarding_altura']);
    unset($_SESSION['onboarding_peso']);

    // 7. Guardar las metas calculadas en la sesión para el Dashboard
    $_SESSION['meta_calorias'] = $calorias_finales;
    $_SESSION['mostrar_tutorial'] = true;

    // 8. ¡Redirigir al Dashboard!
    header("Location: ../views/dashboard.php");
    exit();

} catch(PDOException $e) {
    die("Error al guardar tu perfil: " . $e->getMessage());
}
?>