<?php
// config/db.php
$user_tz = $_COOKIE['user_timezone'] ?? 'America/Tijuana';
if (in_array($user_tz, timezone_identifiers_list())) {
    date_default_timezone_set($user_tz);
} else {
    date_default_timezone_set('America/Tijuana');
}

$host = "localhost";
$dbname = "nutriassist_db"; 
$username = "root";
$password = "contrasena";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Errores en modo Excepción para desarrollo
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Opcional: Para evitar que MySQL emule las sentencias preparadas y use las reales
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // En producción no mostrarías el $e->getMessage() por seguridad, 
    // pero para tu práctica es vital para depurar.
    die("Error crítico de conexión: " . $e->getMessage());
}
?>