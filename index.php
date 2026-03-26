<?php
// 1. Iniciamos la sesión para comprobar si el usuario ya está logueado
session_start();

// 2. Lógica de Enrutamiento:
if (isset($_SESSION['usuario_id'])) {
    // Si ya está logueado, saltamos al dashboard
    header("Location: views/dashboard.php");
    exit();
} else {
    // Si no está logueado o entra por primera vez, redirigir directo al login
    header("Location: views/login.html");
    exit();
}
?>