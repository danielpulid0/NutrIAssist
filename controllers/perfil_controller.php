<?php
require_once '../utils/Auth.php';
$user_id = Auth::requireLogin();
require_once '../config/conexion.php';
require_once '../models/Usuario.php';



// 1. MIGRACIÓN SILENCIOSA
Usuario::ensureColumnsExist($conn);

// 2. OBTENER DATOS DEL USUARIO
$user = Usuario::getById($conn, $user_id);

// Si el usuario no fue encontrado en la base de datos (sesión corrupta/borrada)
if (!$user) {
    header("Location: ../controllers/logout.php");
    exit();
}

$fecha_nacimiento = '1995-01-01'; // Default
$edad = 24; 
if (!empty($user['fecha_nacimiento']) && $user['fecha_nacimiento'] != '0000-00-00') {
    $fecha_nacimiento = $user['fecha_nacimiento'];
    $nacimiento = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento)->y;
}

$peso = (float)$user['peso_kg'] > 0 ? (float)$user['peso_kg'] : 75;
$altura = (int)$user['altura_cm'] > 0 ? (int)$user['altura_cm'] : 178;
$sexo = $user['sexo'] ?? 'Hombre';
$meta = $user['meta_principal'] ?? 'Perder Grasa';
$actividad = $user['id_nivel_actividad'] ?? 2; // Supongamos 2 = Moderado

// 3. OBTENER RESTRICCIONES
$restricciones = Usuario::getRestricciones($conn, $user_id);
