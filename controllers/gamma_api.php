<?php
session_start();
header('Content-Type: application/json');

// 1. Validar seguridad
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

// 2. Leer lo que nos envió Vanilla JS
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);
$mensaje_usuario = $data['prompt'] ?? '';

if (empty($mensaje_usuario)) {
    echo json_encode(['status' => 'error', 'message' => 'El mensaje está vacío']);
    exit();
}

// ==========================================
// CONFIGURACIÓN DE LA API (Google AI Studio - Gemma)
// ==========================================
// ¡CUIDADO! En un proyecto real, esto debe ir en un archivo .env oculto
$api_key = "AIzaSyALQvWdzYhMklLSj9DTh01TVIvmjwyBVKQ"; 
$modelo = "gemma-2-9b-it"; // El modelo Open Source de Google

// La URL de Google AI Studio lleva la llave en la misma URL
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key={$api_key}";

// 3. EL SYSTEM PROMPT (Estructura para Google Generative Language API)
$system_prompt = "Eres un nutricionista experto. Tu única tarea es extraer la información del alimento y estimar sus macronutrientes. 
REGLA ABSOLUTA: Responde ÚNICA Y EXCLUSIVAMENTE con un objeto JSON válido. No uses markdown (```json), no saludes.
Estructura obligatoria:
{
    \"alimento\": \"Nombre del platillo\",
    \"calorias\": 0,
    \"proteina\": 0,
    \"carbs\": 0,
    \"grasas\": 0,
    \"tipo_comida\": \"Desayuno\" // (Puede ser Desayuno, Comida, Cena o Snack)
}";

// Unimos la instrucción estricta con el mensaje del usuario
$prompt_completo = $system_prompt . "\n\nAnaliza lo siguiente: " . $mensaje_usuario;

// 4. Armar el paquete de datos (Payload) específico para Google AI Studio
$payload = json_encode([
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt_completo]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.1, // Súper estricto y matemático
        "responseMimeType" => "application/json" // Forzamos a que devuelva JSON puro
    ]
]);

// 5. INICIO DE LA LLAMADA REAL CON cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Ejecutar petición
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. PROCESAR LA RESPUESTA DE GEMMA
if ($http_code == 200) {
    $respuesta_api = json_decode($response, true);
    
    // Extraer el texto de la estructura de respuesta de Google
    if (isset($respuesta_api['candidates'][0]['content']['parts'][0]['text'])) {
        $contenido_gemma = $respuesta_api['candidates'][0]['content']['parts'][0]['text'];
        
        // Limpiar cualquier markdown residual por si Gemma se pone terca
        $contenido_limpio = preg_replace('/```json|```/', '', $contenido_gemma);
        $json_final = json_decode(trim($contenido_limpio), true);

        if ($json_final) {
            echo json_encode([
                'status' => 'success',
                'food_data' => $json_final
            ]);
        } else {
            // Si Gemma respondió texto normal en lugar de JSON
            echo json_encode(['status' => 'error', 'message' => 'Gemma no devolvió un formato válido.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Respuesta inesperada de la API.']);
    }
} else {
    // Si la llave está mal o no hay internet
    echo json_encode(['status' => 'error', 'message' => "Fallo la conexión con Gemma. HTTP Code: $http_code"]);
}
?>