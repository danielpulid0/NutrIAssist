<?php
session_start();
// Destruimos todas las variables de sesión
$_SESSION = array();
// Destruimos la sesión en el servidor
session_destroy();
// Lo mandamos de regreso a la pantalla principal (Splash)
header("Location: ../index.php");
exit();
?>