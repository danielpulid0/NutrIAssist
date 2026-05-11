<?php
// ============================================================
// controllers/api_alimentos.php — v3 (Bilingüe)
// Defensas:
//   D1: Traducción ES→EN via Gemini (para USDA) + nombre en ES para la DB
//   D2: Búsqueda FULLTEXT en la DB local (caché)
//   D3: Upsert por fdc_id UNIQUE (llave maestra USDA)
// Flujo: "manzana" → {es:"Manzana", en:"Apple"} → busca "Apple" en USDA → guarda "Manzana" en DB → próxima búsqueda: hit local en <10ms
// ============================================================
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin(true);

require_once '../config/conexion.php';
require_once '../config/keys.php';

if (!isset($_GET['query'])) {
    echo json_encode(['status' => 'error', 'message' => 'Búsqueda vacía.']);
    exit();
}

// Obtener la búsqueda cruda del usuario
$busqueda_raw = trim($_GET['query']);

// ──────────────────────────────────────────────────────────────────────────────
// DEFENSA 1: Traducción y canonicalización con Gemini
// El nombre EN se usa para buscar en USDA; el ES para mostrar y guardar.
// ──────────────────────────────────────────────────────────────────────────────
$nombres = traducirYCanonicalizar($busqueda_raw);

/**
 * Llama a Gemini para obtener el nombre canónico del alimento en ES y EN.
 * Siempre usa la API (no tiene bypass por longitud) para garantizar la traducción.
 * En caso de fallo devuelve el texto original como ES y un intento de traducción básico.
 *
 * @return array{es: string, en: string}
 */
function traducirYCanonicalizar(string $texto): array {
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/"
             . GEMINI_MODELO_CHAT . ":generateContent?key=" . GEMINI_API_KEY;

    $prompt =
        "Eres un experto en nutrición. Debes identificar el alimento mencionado y devolver " .
        "su nombre canónico en DOS idiomas. " .
        "REGLAS ESTRICTAS: " .
        "1) Responde ÚNICAMENTE con un objeto JSON válido, sin explicaciones, sin markdown, sin comillas extras. " .
        "2) El formato exacto es: {\"es\":\"Nombre en español\",\"en\":\"Name in English\"}. " .
        "3) Usa el formato 'Sustantivo, descriptor' (ej: es:'Pollo, pierna asada' / en:'Chicken, roasted leg'). " .
        "4) Si es marca o producto, usa el nombre genérico. " .
        "5) El nombre en inglés debe ser exactamente como aparecería en la base de datos USDA FoodData Central. " .
        "Alimento a identificar: \"$texto\"";

    $payload = json_encode([
        "contents" => [[
            "role"  => "user",
            "parts" => [["text" => $prompt]]
        ]],
        "generationConfig" => [
            "temperature"     => 0,      // Respuesta determinista
            "maxOutputTokens" => 60,     // Solo necesitamos el JSON corto
        ]
    ]);

    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 6,
    ]);
    $resp = curl_exec($ch);
    $ok   = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);

    if ($ok) {
        $data  = json_decode($resp, true);
        $raw   = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        // Extraer solo el bloque JSON aunque Gemini añada texto extra
        if (preg_match('/\{[^}]+\}/s', $raw, $m)) {
            $parsed = json_decode($m[0], true);
            $es = trim($parsed['es'] ?? '');
            $en = trim($parsed['en'] ?? '');
            if (!empty($es) && !empty($en) && strlen($es) < 120 && strlen($en) < 120) {
                return ['es' => $es, 'en' => $en];
            }
        }
    }

    // Fallback: devolver el texto original en ambos idiomas
    error_log('[api_alimentos] Gemini no devolvió JSON válido para: ' . $texto);
    $limpio = ucfirst(strtolower(trim($texto)));
    return ['es' => $limpio, 'en' => $limpio];
}

// ──────────────────────────────────────────────────────────────────────────────
// DEFENSA 2: Buscar en caché local (DB) usando el nombre EN ESPAÑOL
// Si ya fue buscado antes, está guardado en español → hit inmediato sin APIs
// ──────────────────────────────────────────────────────────────────────────────
require_once '../models/Alimento.php';

try {
    $alimento_local = Alimento::searchLocal($conn, $nombres['es']);

    if ($alimento_local) {
        echo json_encode([
            'status' => 'success',
            'fuente' => 'mysql_local',
            'nombre_canonico' => $nombres['es'],
            'data'   => $alimento_local
        ]);
        exit();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CACHÉ MISS: consultar USDA FoodData Central en INGLÉS
    // Buscamos con el nombre EN INGLÉS que devolvió Gemini para obtener resultados
    // ──────────────────────────────────────────────────────────────────────────
    $usda_key    = defined('USDA_API_KEY') ? USDA_API_KEY : 'DEMO_KEY';
    $query_usda  = $nombres['en'];  // ← Aquí está el cambio clave: EN inglés
    $url = "https://api.nal.usda.gov/fdc/v1/foods/search?api_key={$usda_key}"
         . "&query=" . urlencode($query_usda) . "&pageSize=1&requireAllWords=true";

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

    // Guardamos con el nombre en ESPAÑOL (el usuario nunca ve el nombre en inglés)
    $nombre_final = $nombres['es'];

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
    // ──────────────────────────────────────────────────────────────────────────
    $datos_nuevo = [
        'nombre'            => $nombre_final,
        'calorias_por_100g' => $calorias,
        'proteina_por_100g' => $proteina,
        'carbs_por_100g'    => $carbs,
        'grasas_por_100g'   => $grasas,
        'fdc_id'            => $fdc_id
    ];
    
    $nuevo_id = Alimento::upsert($conn, $datos_nuevo);

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