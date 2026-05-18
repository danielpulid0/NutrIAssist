<?php
class Receta {
    /**
     * Obtiene una receta sugerida basada en un keyword (etiqueta), 
     * o una receta aleatoria si no encuentra coincidencia.
     * @param PDO $conn Conexión a la base de datos
     * @param string $keyword Palabra clave para buscar en las etiquetas
     * @return array|null Datos de la receta o null si hay un error
     */
    public static function getSuggestedRecipe($conn, $keyword) {
        try {
            // Buscar una receta que contenga el tag necesario
            $stmt_sugerida = $conn->prepare("SELECT * FROM Recetas WHERE etiquetas LIKE :kw ORDER BY RAND() LIMIT 1");
            $stmt_sugerida->execute([':kw' => "%\"$keyword\"%"]);
            $receta_sugerida = $stmt_sugerida->fetch(PDO::FETCH_ASSOC);

            // Si no hay receta con ese tag, obtener una aleatoria
            if (!$receta_sugerida) {
                $stmt_aleatoria = $conn->query("SELECT * FROM Recetas ORDER BY RAND() LIMIT 1");
                $receta_sugerida = $stmt_aleatoria->fetch(PDO::FETCH_ASSOC);
            }
            return $receta_sugerida ?: null;
        } catch (PDOException $e) {
            error_log('[RecetaModel] DB ERROR: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca recetas opcionalmente filtradas por título.
     * @param PDO $conn
     * @param string $termino_busqueda Término de búsqueda
     * @return array Lista de recetas
     */
    public static function search($conn, $termino_busqueda = '') {
        try {
            $sql = "SELECT id_receta, titulo, imagen_url, tiempo_prep_min, calorias_totales, etiquetas FROM Recetas";
            $params = [];
            if ($termino_busqueda !== '') {
                $sql .= " WHERE titulo LIKE :q";
                $params[':q'] = '%' . $termino_busqueda . '%';
            }
            $sql .= " ORDER BY id_receta ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[RecetaModel] DB ERROR search: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una receta por su ID.
     * @param PDO $conn
     * @param int $id_receta
     * @return array|null
     */
    public static function getById($conn, $id_receta) {
        try {
            $stmt = $conn->prepare("SELECT * FROM Recetas WHERE id_receta = :id");
            $stmt->bindParam(':id', $id_receta, PDO::PARAM_INT);
            $stmt->execute();
            $receta = $stmt->fetch(PDO::FETCH_ASSOC);
            return $receta ?: null;
        } catch (PDOException $e) {
            error_log('[RecetaModel] DB ERROR getById: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene los ingredientes de una receta.
     * @param PDO $conn
     * @param int $id_receta
     * @return array
     */
    public static function getIngredients($conn, $id_receta) {
        try {
            $stmt = $conn->prepare("
                SELECT ir.id_ingrediente, ir.cantidad_gramos, a.nombre,
                       ROUND(a.calorias_por_100g * ir.cantidad_gramos / 100) AS calorias_calc,
                       ROUND(a.proteina_por_100g * ir.cantidad_gramos / 100, 1) AS proteina_calc,
                       ROUND(a.carbs_por_100g * ir.cantidad_gramos / 100, 1) AS carbs_calc,
                       ROUND(a.grasas_por_100g * ir.cantidad_gramos / 100, 1) AS grasas_calc
                FROM Ingredientes_Receta ir
                JOIN Alimentos a ON ir.id_alimento = a.id_alimento
                WHERE ir.id_receta = :id
            ");
            $stmt->bindParam(':id', $id_receta, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[RecetaModel] DB ERROR getIngredients: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extrae el nivel de costo de las etiquetas de una receta.
     * @param array $tags Etiquetas decodificadas de la receta
     * @return string Uno de: 'Económico', 'Medio', 'Caro', 'Premium'
     */
    public static function extractCosto($tags) {
        $costos_map = [
            'economico'  => 'Económico',
            'económico'  => 'Económico',
            'medio'      => 'Medio',
            'caro'       => 'Costoso',
            'costoso'    => 'Costoso'
        ];
        foreach ($tags as $tag) {
            $normalized = strtolower(trim($tag));
            if (isset($costos_map[$normalized])) {
                return $costos_map[$normalized];
            }
        }
        return 'Medio';
    }
}
?>
