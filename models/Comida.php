<?php
class Comida {
    /**
     * Guarda un registro de alimentos consumidos (Snapshot).
     * Garantiza la existencia del registro diario, crea el bloque de comida (ej. 'Desayuno')
     * e inserta los alimentos asociados.
     * 
     * @param PDO $conn Conexión a DB
     * @param int $id_usuario ID del usuario
     * @param string $fecha Fecha del registro (Y-m-d)
     * @param string $tipo Tipo de comida (Desayuno, Comida, Cena, Snack)
     * @param array $items Lista de alimentos (arreglo asociativo)
     * @return int ID del bloque de comida creado
     * @throws PDOException
     */
    public static function saveFoodLog($conn, $id_usuario, $fecha, $tipo, $items) {
        // Paso A: Garantizar el registro diario
        $stmt_ign = $conn->prepare("INSERT IGNORE INTO Registros_Diarios (id_usuario, fecha) VALUES (?,?)");
        $stmt_ign->execute([$id_usuario, $fecha]);
        
        $stmt_sel = $conn->prepare("SELECT id_registro FROM Registros_Diarios WHERE id_usuario=? AND fecha=? LIMIT 1");
        $stmt_sel->execute([$id_usuario, $fecha]);
        $id_registro = $stmt_sel->fetchColumn();

        // Paso B: Crear bloque de comida
        $stmt_com = $conn->prepare("INSERT INTO Comidas (id_registro, tipo) VALUES (?,?)");
        $stmt_com->execute([$id_registro, $tipo]);
        $id_comida = $conn->lastInsertId();

        // Paso C: Insertar cada alimento con patrón Snapshot
        $stmt_item = $conn->prepare("
            INSERT INTO Alimentos_Consumidos
                (id_comida, id_alimento, cantidad_gramos, nombre_ia, calorias_ia, proteina_ia, carbs_ia, grasas_ia)
            VALUES
                (:id_comida, :id_alim, :gramos, :nombre, :cal, :prot, :carbs, :gras)
        ");

        foreach ($items as $it) {
            $stmt_item->execute([
                ':id_comida' => $id_comida,
                ':id_alim'   => $it['id_alim'] ?? null,
                ':gramos'    => $it['gramos'] ?? 0,
                ':nombre'    => $it['nombre'],
                ':cal'       => $it['calorias'],
                ':prot'      => $it['proteina'],
                ':carbs'     => $it['carbs'],
                ':gras'      => $it['grasas'],
            ]);
        }
        
        return $id_comida;
    }
}
?>
