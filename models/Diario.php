<?php
class Diario {
    /**
     * Obtiene los macros totales consumidos por un usuario en una fecha específica.
     * @param PDO $conn Conexión a la base de datos
     * @param int $id_usuario ID del usuario
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @return array Arreglo con total_cal, total_pro, total_carbs, total_grasas
     */
    public static function getDailyMacros($conn, $id_usuario, $fecha) {
        try {
            $stmt = $conn->prepare("
                SELECT
                    COALESCE(SUM(ac.calorias_ia), 0) AS total_cal,
                    COALESCE(SUM(ac.proteina_ia), 0) AS total_pro,
                    COALESCE(SUM(ac.carbs_ia),    0) AS total_carbs,
                    COALESCE(SUM(ac.grasas_ia),   0) AS total_grasas
                FROM Alimentos_Consumidos ac
                INNER JOIN Comidas c
                    ON ac.id_comida = c.id_comida
                INNER JOIN Registros_Diarios rd
                    ON c.id_registro = rd.id_registro
                WHERE rd.id_usuario = :uid
                  AND rd.fecha      = :fecha
            ");
            $stmt->execute([':uid' => $id_usuario, ':fecha' => $fecha]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[DiarioModel] DB ERROR: ' . $e->getMessage());
            return [
                'total_cal' => 0,
                'total_pro' => 0,
                'total_carbs' => 0,
                'total_grasas' => 0
            ];
        }
    }

    /**
     * Obtiene los alimentos consumidos en una fecha específica, agrupados por tipo de comida.
     * @param PDO $conn Conexión a la base de datos
     * @param int $id_usuario ID del usuario
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @return array Arreglo asociativo con los tipos de comida como llaves
     */
    public static function getFoodLogGroups($conn, $id_usuario, $fecha) {
        $comidas_grupos = [];
        try {
            $stmt = $conn->prepare("
                SELECT c.tipo AS tipo_comida,
                       ac.id_consumo,
                       ac.nombre_ia  AS nombre,
                       ac.calorias_ia AS calorias,
                       ac.proteina_ia AS proteina,
                       ac.carbs_ia    AS carbs,
                       ac.grasas_ia   AS grasas
                FROM Alimentos_Consumidos ac
                INNER JOIN Comidas c ON ac.id_comida = c.id_comida
                INNER JOIN Registros_Diarios rd ON c.id_registro = rd.id_registro
                WHERE rd.id_usuario = :uid AND rd.fecha = :fecha
                ORDER BY c.id_comida ASC, ac.id_consumo ASC
            ");
            $stmt->execute([':uid' => $id_usuario, ':fecha' => $fecha]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $comidas_grupos[$row['tipo_comida']][] = $row;
            }
        } catch (PDOException $e) {
            error_log('[DiarioModel] DB ERROR getFoodLogGroups: ' . $e->getMessage());
        }
        return $comidas_grupos;
    }

    /**
     * Obtiene el reporte de macros por día en un rango de fechas.
     * @param PDO $conn
     * @param int $id_usuario
     * @param string $fecha_inicio
     * @param string $fecha_fin
     * @return array
     */
    public static function getWeeklyReport($conn, $id_usuario, $fecha_inicio, $fecha_fin) {
        try {
            $stmt = $conn->prepare("
                SELECT 
                    rd.fecha,
                    COALESCE(SUM(ac.calorias_ia), 0) AS cals,
                    COALESCE(SUM(ac.proteina_ia), 0) AS pro,
                    COALESCE(SUM(ac.carbs_ia), 0) AS car,
                    COALESCE(SUM(ac.grasas_ia), 0) AS gra
                FROM Registros_Diarios rd
                LEFT JOIN Comidas c ON rd.id_registro = c.id_registro
                LEFT JOIN Alimentos_Consumidos ac ON c.id_comida = ac.id_comida
                WHERE rd.id_usuario = :uid AND rd.fecha BETWEEN :inicio AND :fin
                GROUP BY rd.fecha
                ORDER BY rd.fecha ASC
            ");
            $stmt->execute([
                ':uid' => $id_usuario,
                ':inicio' => $fecha_inicio,
                ':fin' => $fecha_fin
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[DiarioModel] DB ERROR getWeeklyReport: ' . $e->getMessage());
            return [];
        }
    }
}
?>
