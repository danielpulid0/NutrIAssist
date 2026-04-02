<?php
// ============================================================
// controllers/api_alimentos.php — v2 (Anti-duplicados)
// Implementa las 3 defensas contra duplicidad semántica:
//   D1: Canonicalización por IA (via Gemma)
//   D2: Búsqueda FULLTEXT en lugar de LIKE
//   D3: Upsert por fdc_id UNIQUE (llave maestra USDA)
// ============================================================
session_start();
header('Content-Type: application/json');

require_once '../config/conexion.php';
require_once '../config/keys.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['query'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado o búsqueda vacía.']);
    exit();
}

$busqueda_raw = trim($_GET['query']);

// ──────────────────────────────────────────────────────────────────────────────
// DEFENSA 1: Canonicalización via Gemma
// Pedimos a la IA que estandarice el nombre antes de buscar.
// "piernitas de pollo" → "Pierna de pollo"
// ──────────────────────────────────────────────────────────────────────────────
$busqueda = canonicalizar($busqueda_raw);

function canonicalizar(string $texto): string {
    // Si el texto ya es corto y simple, no gastas un token
    if (strlen($texto) <= 15 && !preg_match('/\s{2,}|[^\w\s\-\(\)\.áéíóúñ]/i', $texto)) {
        return ucfirst(strtolower(trim($texto)));
    }

    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/"
             . GEMINI_MODELO_CHAT . ":generateContent?key=" . GEMINI_API_KEY;

    $payload = json_encode([
        "contents" => [[
            "role"  => "user",
            "parts" => [["text" =>
                "Eres un experto en nutrición. Tu única tarea es devolver el nombre canónico " .
                "estandarizado del alimento que el usuario menciona. " .
                "REGLAS: 1) Devuelve SOLO el nombre, sin explicaciones ni puntuación. " .
                "2) Usa el formato 'Sustantivo, descriptor' (ej: 'Pollo, pierna asada'). " .
                "3) Si es marca o producto, usa el nombre genérico. " .
                "Alimento a normalizar: \"$texto\""
            ]]
        ]]
    ]);

    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 5, // no bloquear más de 5s
    ]);
    $resp = curl_exec($ch);
    $ok   = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);

    if ($ok) {
        $data = json_decode($resp, true);
        $canon = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $canon = trim(strip_tags($canon));
        if (!empty($canon) && strlen($canon) < 80) {
            return $canon;
        }
    }

    // Fallback: limpiar al menos el texto original
    return ucfirst(strtolower(trim($texto)));
}

// ──────────────────────────────────────────────────────────────────────────────
// DEFENSA 2: FULLTEXT SEARCH (en lugar de LIKE)
// Ignora stop words ("de", "el", "la") para encontrar coincidencias semánticas.
// Requiere que la columna 'nombre' tenga FULLTEXT INDEX (ver nota al pie).
// ──────────────────────────────────────────────────────────────────────────────
try {
    // Intentamos FULLTEXT primero; si el índice no existe, caemos a LIKE
    $alimento_local = null;

    try {
        $stmt = $conn->prepare("
            SELECT *, MATCH(nombre) AGAINST (:q IN NATURAL LANGUAGE MODE) AS score
            FROM Alimentos
            WHERE MATCH(nombre) AGAINST (:q2 IN NATURAL LANGUAGE MODE)
            ORDER BY score DESC
            LIMIT 1
        ");
        $stmt->execute([':q' => $busqueda, ':q2' => $busqueda]);
        $alimento_local = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $ftEx) {
        // El índice FULLTEXT no existe aún → fallback a LIKE
        error_log('[api_alimentos] FULLTEXT no disponible, usando LIKE: ' . $ftEx->getMessage());
        $stmt = $conn->prepare("SELECT * FROM Alimentos WHERE nombre LIKE :q LIMIT 1");
        $stmt->execute([':q' => '%' . $busqueda . '%']);
        $alimento_local = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($alimento_local) {
        echo json_encode([
            'status' => 'success',
            'fuente' => 'mysql_local',
            'nombre_canonico' => $busqueda, // para debugging
            'data'   => $alimento_local
        ]);
        exit();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CACHÉ MISS: consultar USDA FoodData Central
    // ──────────────────────────────────────────────────────────────────────────
    $usda_key = defined('USDA_API_KEY') ? USDA_API_KEY : 'DEMO_KEY';
    $url = "https://api.nal.usda.gov/fdc/v1/foods/search?api_key={$usda_key}"
         . "&query=" . urlencode($busqueda) . "&pageSize=1&requireAllWords=true";

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        echo json_encode(['status' => 'error', 'message' => 'Error de conexión con FoodData Central.']);
        exit();
    }

    $data_usda = json_decode($response, true);

    if (empty($data_usda['foods'])) {
        echo json_encode(['status' => 'error', 'message' => 'El alimento no existe en el registro nutricional.']);
        exit();
    }

    $food = $data_usda['foods'][0];

    // Usar el nombre canónico (D1) en lugar del description crudo de la API
    $nombre_final = $busqueda; // ya fue canonicalizado antes

    $fdc_id   = (int) ($food['fdcId'] ?? 0);
    $calorias = $proteina = $carbs = $grasas = 0;

    foreach ($food['foodNutrients'] as $n) {
        switch ($n['nutrientId']) {
            case 1008: $calorias = $n['value']; break;
            case 1003: $proteina = $n['value']; break;
            case 1005: $carbs    = $n['value']; break;
            case 1004: $grasas   = $n['value']; break;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DEFENSA 3: INSERT con fdc_id UNIQUE → previene duplicados matemáticamente
    // ON DUPLICATE KEY UPDATE actualiza si ya existe (en vez de lanzar error)
    // ──────────────────────────────────────────────────────────────────────────
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
        ':nombre'  => $nombre_final,
        ':cal'     => $calorias,
        ':pro'     => $proteina,
        ':car'     => $carbs,
        ':gra'     => $grasas,
        ':fdc_id'  => $fdc_id ?: null,
    ]);

    // Obtener el id real (sea insert nuevo o el que ya existía)
    $nuevo_id = $conn->lastInsertId() ?: (function() use ($conn, $fdc_id, $nombre_final) {
        $s = $conn->prepare("SELECT id_alimento FROM Alimentos WHERE fdc_id = ? OR nombre = ? LIMIT 1");
        $s->execute([$fdc_id, $nombre_final]);
        return $s->fetchColumn();
    })();

    echo json_encode([
        'status'  => 'success',
        'fuente'  => 'api_usda_guardado',
        'nombre_canonico' => $nombre_final,
        'data'    => [
            'id_alimento'      => $nuevo_id,
            'nombre'           => $nombre_final,
            'fdc_id'           => $fdc_id,
            'calorias_por_100g'=> $calorias,
            'proteina_por_100g'=> $proteina,
            'carbs_por_100g'   => $carbs,
            'grasas_por_100g'  => $grasas,
        ]
    ]);

} catch (PDOException $e) {
    error_log('[api_alimentos] ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos.']);
}
?>