<?php
session_start();
require_once '../config/conexion.php';

// Validar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['usuario_id'];

// 1. ELIMINAR RESTRICCIÓN (Via URL GET)
if (isset($_GET['eliminar_rest']) && is_numeric($_GET['eliminar_rest'])) {
    $rest_id = (int)$_GET['eliminar_rest'];
    $stmt = $conn->prepare("DELETE FROM Usuario_Restriccion WHERE id_usuario = :uid AND id_restriccion = :rid");
    $stmt->bindParam(':uid', $user_id);
    $stmt->bindParam(':rid', $rest_id);
    $stmt->execute();
    header("Location: ../views/perfil.php");
    exit();
}

// 2. ACTUALIZAR PERFIL Y AGREGAR RESTRICCIÓN (Via POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Biométricos ---
    $edad = (int)$_POST['edad'];
    $peso = (float)$_POST['peso'];
    $altura = (int)$_POST['altura'];
    $sexo = $_POST['sexo'];
    $actividad = (int)$_POST['actividad'];
    $meta = $_POST['meta_principal'];

    // Aproximar fecha de nacimiento usando la edad
    $anio_nac = date('Y') - $edad;
    $fecha_nac = "$anio_nac-01-01";

    try {
        $sql = "UPDATE Usuarios 
                SET fecha_nacimiento = :fecha, 
                    peso_kg = :peso, 
                    altura_cm = :altura, 
                    sexo = :sexo, 
                    id_nivel_actividad = :act, 
                    meta_principal = :meta 
                WHERE id_usuario = :id";
                
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':fecha', $fecha_nac);
        $stmt->bindParam(':peso', $peso);
        $stmt->bindParam(':altura', $altura);
        $stmt->bindParam(':sexo', $sexo);
        $stmt->bindParam(':act', $actividad);
        $stmt->bindParam(':meta', $meta);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        // --- Nueva Restricción ---
        if (!empty(trim($_POST['nueva_restriccion']))) {
            $nombre_rest = trim($_POST['nueva_restriccion']);
            
            // Buscar si ya existe la restricción en el catálogo global
            $stmt_buscar = $conn->prepare("SELECT id_restriccion FROM Restricciones_Medicas WHERE nombre = :nombre LIMIT 1");
            $stmt_buscar->bindParam(':nombre', $nombre_rest);
            $stmt_buscar->execute();
            $rest = $stmt_buscar->fetch(PDO::FETCH_ASSOC);
            
            if ($rest) {
                $id_res = $rest['id_restriccion'];
            } else {
                // Insertarla
                $stmt_insert = $conn->prepare("INSERT INTO Restricciones_Medicas (nombre) VALUES (:nombre)");
                $stmt_insert->bindParam(':nombre', $nombre_rest);
                $stmt_insert->execute();
                $id_res = $conn->lastInsertId();
            }
            
            // Vincularla al usuario usando IGNORE para evitar error si ya la tenía
            $stmt_link = $conn->prepare("INSERT IGNORE INTO Usuario_Restriccion (id_usuario, id_restriccion) VALUES (:u, :r)");
            $stmt_link->bindParam(':u', $user_id);
            $stmt_link->bindParam(':r', $id_res);
            $stmt_link->execute();
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
