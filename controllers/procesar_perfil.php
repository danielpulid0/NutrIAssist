<?php
require_once '../utils/Auth.php';
$user_id = Auth::requireLogin();
require_once '../config/conexion.php';
require_once '../models/Usuario.php';

// 1. ELIMINAR RESTRICCIÓN (Via URL GET)
if (isset($_GET['eliminar_rest']) && is_numeric($_GET['eliminar_rest'])) {
    $rest_id = (int)$_GET['eliminar_rest'];
    Usuario::removeRestriccion($conn, $user_id, $rest_id);
    header("Location: ../views/perfil.php");
    exit();
}

// 2. ACTUALIZAR PERFIL Y AGREGAR RESTRICCIÓN (Via POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once '../utils/Validator.php';
    $validator = new Validator();

    // --- Biométricos ---
    $fecha_nac = $_POST['fecha_nacimiento'] ?? '1995-01-01';
    $peso = $_POST['peso'] ?? 0;
    $altura = $_POST['altura'] ?? 0;
    $sexo = $_POST['sexo'] ?? '';
    $actividad = $_POST['actividad'] ?? 0;
    $meta = $_POST['meta_principal'] ?? '';

    $validator->required($_POST, ['fecha_nacimiento', 'peso', 'altura', 'sexo', 'actividad', 'meta_principal'])
              ->numeric($peso, 'peso')->min($peso, 15, 'peso')->max($peso, 635, 'peso')
              ->numeric($altura, 'altura')->min($altura, 50, 'altura')->max($altura, 245, 'altura');

    if ($validator->hasErrors()) {
        $error = $validator->getFirstError();
        header("Location: ../views/perfil.php?error=$error");
        exit();
    }

    $peso = (float)$peso;
    $altura = (int)$altura;
    $actividad = (int)$actividad;

    // Calcular edad real para la fórmula
    $cumpleanos = new DateTime($fecha_nac);
    $hoy = new DateTime();
    $edad = $hoy->diff($cumpleanos)->y;

    $multiplicadores = [
        1 => 1.200, 
        2 => 1.375, 
        3 => 1.550, 
        4 => 1.725
    ];
    $factor_actividad = $multiplicadores[$actividad] ?? 1.200;

    $metas = Usuario::calcularMetasNutricionales($peso, $altura, $edad, $sexo, $factor_actividad, $meta);

    // Actualizar la meta de calorías y macros en la sesión
    $_SESSION['meta_calorias'] = $metas['calorias'];
    $_SESSION['meta_proteina'] = $metas['proteinas'];
    $_SESSION['meta_grasas']   = $metas['grasas'];
    $_SESSION['meta_carbs']    = $metas['carbos'];

    // --- MIGRACIÓN SILENCIOSA DEL CATÁLOGO DE ACTIVIDAD ---
    Usuario::ensureActivityLevelsExist($conn);

    try {
        $datos_perfil = [
            'fecha_nacimiento' => $fecha_nac,
            'peso_kg' => $peso,
            'altura_cm' => $altura,
            'sexo' => $sexo,
            'actividad' => $actividad,
            'meta_principal' => $meta
        ];
        
        Usuario::updateProfile($conn, $user_id, $datos_perfil);
        
        // --- Nueva Restricción ---
        if (!empty(trim($_POST['nueva_restriccion']))) {
            $nombre_rest = trim($_POST['nueva_restriccion']);
            Usuario::addRestriccion($conn, $user_id, $nombre_rest);
        }

        // Redirigir de regreso exitosamente
        header("Location: ../views/perfil.php?exito=1");
        exit();

    } catch (PDOException $e) {
        die("Error al actualizar perfil: " . $e->getMessage());
    }
}

// Si no fue GET de eliminar o POST, devolver al perfil
header("Location: ../views/perfil.php");
exit();
