<?php
class Usuario {
    /**
     * Obtiene los datos de un usuario por su ID
     */
    public static function getById($conn, $id) {
        try {
            $stmt = $conn->prepare("SELECT * FROM Usuarios WHERE id_usuario = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[UsuarioModel] DB ERROR getById: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las restricciones médicas de un usuario
     */
    public static function getRestricciones($conn, $id) {
        try {
            $stmt = $conn->prepare("
                SELECT r.id_restriccion, r.nombre 
                FROM Restricciones_Medicas r
                JOIN Usuario_Restriccion ur ON r.id_restriccion = ur.id_restriccion
                WHERE ur.id_usuario = :id
            ");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[UsuarioModel] DB ERROR getRestricciones: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Migración silenciosa (Asegurar que las columnas existan)
     */
    public static function ensureColumnsExist($conn) {
        try {
            $conn->query("SELECT sexo FROM Usuarios LIMIT 1");
        } catch (PDOException $e) {
            $conn->exec("ALTER TABLE Usuarios ADD COLUMN sexo ENUM('Hombre', 'Mujer') DEFAULT 'Hombre'");
            $conn->exec("ALTER TABLE Usuarios ADD COLUMN meta_principal ENUM('Perder Grasa', 'Ganar Músculo', 'Mantener Peso') DEFAULT 'Perder Grasa'");
        }
    }

    /**
     * Obtiene los datos de un usuario por su email
     * @param PDO $conn
     * @param string $email
     * @return array|false
     */
    public static function getByEmail($conn, $email) {
        try {
            $sql = "SELECT id_usuario, nombre, password_hash FROM Usuarios WHERE email = :email LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[UsuarioModel] DB ERROR getByEmail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuevo usuario en la base de datos
     * @param PDO $conn
     * @param array $data Datos del usuario (email, password_hash, nombre, fecha_nacimiento, peso_kg, altura_cm)
     * @return int|false El ID del nuevo usuario o false si falla
     * @throws PDOException
     */
    public static function create($conn, $data) {
        $sql = "INSERT INTO Usuarios (email, password_hash, nombre, fecha_nacimiento, peso_kg, altura_cm) 
                VALUES (:email, :password_hash, :nombre, :fecha, :peso, :altura)";
        $stmt = $conn->prepare($sql);
        
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password_hash', $data['password_hash']);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':fecha', $data['fecha_nacimiento']);
        $stmt->bindParam(':peso', $data['peso_kg']);
        $stmt->bindParam(':altura', $data['altura_cm']);
        
        if ($stmt->execute()) {
            return $conn->lastInsertId();
        }
        return false;
    }

    public static function updateProfile($conn, $id, $data) {
        $sql = "UPDATE Usuarios 
                SET fecha_nacimiento = :fecha, 
                    peso_kg = :peso, 
                    altura_cm = :altura, 
                    sexo = :sexo, 
                    id_nivel_actividad = :act, 
                    meta_principal = :meta 
                WHERE id_usuario = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':fecha', $data['fecha_nacimiento']);
        $stmt->bindParam(':peso', $data['peso_kg']);
        $stmt->bindParam(':altura', $data['altura_cm']);
        $stmt->bindParam(':sexo', $data['sexo']);
        $stmt->bindParam(':act', $data['actividad']);
        $stmt->bindParam(':meta', $data['meta_principal']);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public static function updateOnboarding($conn, $id, $data) {
        $sql = "UPDATE Usuarios 
                SET fecha_nacimiento = :fecha, 
                    peso_kg = :peso, 
                    altura_cm = :altura 
                WHERE id_usuario = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':fecha', $data['fecha_nacimiento']);
        $stmt->bindParam(':peso', $data['peso_kg']);
        $stmt->bindParam(':altura', $data['altura_cm']);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public static function removeRestriccion($conn, $user_id, $rest_id) {
        $stmt = $conn->prepare("DELETE FROM Usuario_Restriccion WHERE id_usuario = :uid AND id_restriccion = :rid");
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':rid', $rest_id);
        return $stmt->execute();
    }

    public static function addRestriccion($conn, $user_id, $nombre_rest) {
        $stmt_buscar = $conn->prepare("SELECT id_restriccion FROM Restricciones_Medicas WHERE nombre = :nombre LIMIT 1");
        $stmt_buscar->bindParam(':nombre', $nombre_rest);
        $stmt_buscar->execute();
        $rest = $stmt_buscar->fetch(PDO::FETCH_ASSOC);
        
        if ($rest) {
            $id_res = $rest['id_restriccion'];
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO Restricciones_Medicas (nombre) VALUES (:nombre)");
            $stmt_insert->bindParam(':nombre', $nombre_rest);
            $stmt_insert->execute();
            $id_res = $conn->lastInsertId();
        }
        
        $stmt_link = $conn->prepare("INSERT IGNORE INTO Usuario_Restriccion (id_usuario, id_restriccion) VALUES (:u, :r)");
        $stmt_link->bindParam(':u', $user_id);
        $stmt_link->bindParam(':r', $id_res);
        return $stmt_link->execute();
    }

    public static function ensureActivityLevelsExist($conn) {
        $stmt_check_act = $conn->query("SELECT COUNT(*) FROM Nivel_Actividad");
        if ($stmt_check_act->fetchColumn() == 0) {
            $conn->exec("INSERT INTO Nivel_Actividad (id_nivel_actividad, descripcion, multiplicador_biometrico) VALUES 
                (1, 'Sedentario', 1.200), 
                (2, 'Moderado', 1.550), 
                (3, 'Activo', 1.725)");
        }
    }
}
?>
