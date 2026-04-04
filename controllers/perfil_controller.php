<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}
require_once '../config/conexion.php';

$user_id = $_SESSION['usuario_id'];

// 1. MIGRACIÓN SILENCIOSA (Asegurar que las columnas existan en la base de datos de la fase actual)
try {
    $conn->query("SELECT sexo FROM Usuarios LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE Usuarios ADD COLUMN sexo ENUM('Hombre', 'Mujer') DEFAULT 'Hombre'");
    $conn->exec("ALTER TABLE Usuarios ADD COLUMN meta_principal ENUM('Perder Grasa', 'Ganar Músculo', 'Mantener Peso') DEFAULT 'Perder Grasa'");
}

// 2. OBTENER DATOS DEL USUARIO
$stmt = $conn->prepare("SELECT * FROM Usuarios WHERE id_usuario = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el usuario no fue encontrado en la base de datos (sesión corrupta/borrada)
if (!$user) {
    header("Location: ../controllers/logout.php");
    exit();
}

$edad = 24; // Default
if (!empty($user['fecha_nacimiento']) && $user['fecha_nacimiento'] != '0000-00-00') {
    $nacimiento = new DateTime($user['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento)->y;
}

$peso = (float)$user['peso_kg'] > 0 ? (float)$user['peso_kg'] : 75;
$altura = (int)$user['altura_cm'] > 0 ? (int)$user['altura_cm'] : 178;
$sexo = $user['sexo'] ?? 'Hombre';
$meta = $user['meta_principal'] ?? 'Perder Grasa';
$actividad = $user['id_nivel_actividad'] ?? 2; // Supongamos 2 = Moderado

// 3. OBTENER RESTRICCIONES
$stmt_rest = $conn->prepare("
    SELECT r.id_restriccion, r.nombre 
    FROM Restricciones_Medicas r
    JOIN Usuario_Restriccion ur ON r.id_restriccion = ur.id_restriccion
    WHERE ur.id_usuario = :id
");
$stmt_rest->bindParam(':id', $user_id);
$stmt_rest->execute();
$restricciones = $stmt_rest->fetchAll(PDO::FETCH_ASSOC);
