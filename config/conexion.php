<?php
// config/db.php
$host = "localhost";
$dbname = "nutrIAssist_db"; // Ajusta al nombre exacto que creaste
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