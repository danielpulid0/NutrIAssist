<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin();
Auth::requirePost();

require_once '../config/conexion.php';
require_once '../models/Usuario.php';
$objetivo   = $_SESSION['onboarding_objetivo']; // 'perder_grasa', 'mantener_peso', 'ganar_musculo'
$sexo       = $_SESSION['onboarding_sexo'];     // 'M' o 'F'
$fecha_nacimiento = $_SESSION['onboarding_fecha_nacimiento'];
$altura     = (float)$_SESSION['onboarding_altura'];
$peso       = (float)$_SESSION['onboarding_peso'];
$actividad  = (float)$_POST['actividad'];       // Ej. 1.2, 1.55

// Calcular edad desde fecha de nacimiento para la fórmula
$cumpleanos = new DateTime($fecha_nacimiento);
$hoy = new DateTime();
$edad = $hoy->diff($cumpleanos)->y;

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

try {
    // Mapear objetivo a formato legible para DB
    $meta_db = 'Perder Grasa';
    if ($objetivo === 'mantener_peso') $meta_db = 'Mantener Peso';
    elseif ($objetivo === 'ganar_musculo') $meta_db = 'Ganar Músculo';

    // Mapear sexo a formato legible
    $sexo_db = ($sexo === 'M') ? 'Hombre' : 'Mujer';

    // Mapeo de multiplicador a ID de tabla Nivel_Actividad
    $actividad_id = 1; // Default
    if ($actividad <= 1.25) $actividad_id = 1;      // Sedentario (1.2)
    elseif ($actividad <= 1.4) $actividad_id = 2;   // Ligeramente activo (1.375) -> Lo mapeamos a moderado o creamos más? 
                                                     // La tabla Nivel_Actividad solo tiene 1, 2, 3.
                                                     // Vamos a dejarlo así: 1=Sedentario, 2=Moderado (incluye ligero), 3=Activo
    elseif ($actividad <= 1.6) $actividad_id = 2;   // Moderadamente activo (1.55)
    else $actividad_id = 3;                         // Muy activo (1.725)

    // 5. Actualizar la base de datos a través del modelo
    $datos_onboarding = [
        'fecha_nacimiento' => $fecha_nacimiento,
        'peso_kg' => $peso,
        'altura_cm' => $altura,
        'sexo' => $sexo_db,
        'actividad' => $actividad_id,
        'meta_principal' => $meta_db
    ];
    Usuario::updateOnboarding($conn, $id_usuario, $datos_onboarding);

    // 6. Limpiar la memoria (buenas prácticas de seguridad)
    unset($_SESSION['onboarding_objetivo']);
    unset($_SESSION['onboarding_sexo']);
    unset($_SESSION['onboarding_fecha_nacimiento']);
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