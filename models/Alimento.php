<?php
class Alimento {
    /**
     * Busca un alimento en la base de datos local usando FULLTEXT o LIKE como fallback.
     * @param PDO $conn
     * @param string $nombre Nombre a buscar
     * @return array|false Datos del alimento o false si no existe
     */
    public static function searchLocal($conn, $nombre) {
        try {
            $stmt = $conn->prepare("
                SELECT *, MATCH(nombre) AGAINST (:q IN NATURAL LANGUAGE MODE) AS score
                FROM Alimentos
                WHERE MATCH(nombre) AGAINST (:q2 IN NATURAL LANGUAGE MODE)
                ORDER BY score DESC
                LIMIT 1
            ");
            $stmt->execute([':q' => $nombre, ':q2' => $nombre]);
            $alimento_local = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($alimento_local) return $alimento_local;
        } catch (PDOException $ftEx) {
            // Fallback a LIKE si no hay índice FULLTEXT
            $stmt = $conn->prepare("SELECT * FROM Alimentos WHERE nombre LIKE :q LIMIT 1");
            $stmt->execute([':q' => '%' . $nombre . '%']);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Guarda o actualiza un alimento en el catálogo local (usando el fdc_id de USDA).
     * @param PDO $conn
     * @param array $data Datos del alimento
     * @return int ID del alimento insertado o actualizado
     * @throws PDOException
     */
    public static function upsert($conn, $data) {
        $stmt = $conn->prepare("
            INSERT INTO Alimentos (nombre, calorias_por_100g, proteina_por_100g, carbs_por_100g, grasas_por_100g, fdc_id)
            VALUES (:nombre, :cal, :pro, :car, :gra, :fdc_id)
            ON DUPLICATE KEY UPDATE
                nombre             = VALUES(nombre),
                calorias_por_100g  = VALUES(calorias_por_100g),
                proteina_por_100g  = VALUES(proteina_por_100g),
                carbs_por_100g     = VALUES(carbs_por_100g),
                grasas_por_100g    = VALUES(grasas_por_100g)
        ");
        $stmt->execute([
            ':nombre'  => $data['nombre'],
            ':cal'     => $data['calorias_por_100g'],
            ':pro'     => $data['proteina_por_100g'],
            ':car'     => $data['carbs_por_100g'],
            ':gra'     => $data['grasas_por_100g'],
            ':fdc_id'  => $data['fdc_id'] ?: null,
        ]);

        $nuevo_id = $conn->lastInsertId();
        if (!$nuevo_id) {
            $s = $conn->prepare("SELECT id_alimento FROM Alimentos WHERE fdc_id = ? OR nombre = ? LIMIT 1");
            $s->execute([$data['fdc_id'], $data['nombre']]);
            $nuevo_id = $s->fetchColumn();
        }
        return $nuevo_id;
    }
}
?>
