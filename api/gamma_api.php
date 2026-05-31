<?php
// Habilitar reporte de errores para depuración (ERROR DE RED)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../utils/Auth.php';
$usuario_id = Auth::requireLogin(true);
Auth::requirePost();

require_once '../config/conexion.php';
require_once '../config/keys.php';
require_once '../models/Usuario.php';

// 1. Obtener perfil de base de datos
$perfil_texto = obtenerPerfilTexto($conn, $usuario_id);

// 2. Leer JSON del Frontend
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);
$mensaje_usuario = $data['prompt'] ?? '';
$historial_js = $data['history'] ?? [];
$imagen_b64 = $data['image'] ?? null;
$audio_b64 = $data['audio'] ?? null;

if (empty($mensaje_usuario) && empty($imagen_b64) && empty($audio_b64)) {
    echo json_encode(['status' => 'error', 'message' => 'El mensaje está vacío']);
    exit();
}

// 3. Configurar el endpoint dinámico (IA Híbrida)
$modelo_usar = !empty($audio_b64) ? GEMINI_MODELO_VOZ : GEMINI_MODELO_CHAT;
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo_usar}:generateContent?key=" . GEMINI_API_KEY;

// 4. System Prompt
$system_prompt = "Eres NutrIAssist, un inteligente asistente nutricional creado para chatear, analizar y recomendar comida.
REGLA ABSOLUTA: Responde SIEMPRE con un objeto JSON válido. Sé extremadamente conciso en tus respuestas. Evita introducciones largas, rodeos o descripciones excesivas. Ve directo al grano.
REGLA CRÍTICA DE FORMATO: Los campos numéricos (calorias, proteina, carbs, grasas) DEBEN ser NÚMEROS PUROS sin unidades. Ejemplo correcto: \"calorias\": 350. Ejemplo INCORRECTO: \"calorias\": \"350 kcal\". NUNCA uses strings para valores numéricos. NUNCA agregues unidades como 'kcal', 'g', 'gr' dentro del valor.
Estructuras JSON permitidas según el Caso:

CASO A) Saludo o charla general:
{ \"tipo_respuesta\": \"chat\", \"mensaje_respuesta\": \"[Tu respuesta amigable y natural aquí]\" }

CASO B) El usuario reporta una comida explícitamente consumida:
{ \"tipo_respuesta\": \"food_log\", \"alimento\": \"[Deduce nombre corto sin apóstrofes ni comillas]\", \"descripcion\": \"[Porción estimada]\", \"calorias\": 350, \"proteina\": 25, \"carbs\": 40, \"grasas\": 10, \"tipo_comida\": \"Comida\", \"tipo_icono\": \"solid\" }
ATENCIÓN: calorias, proteina, carbs, grasas DEBEN ser números enteros o decimales, NUNCA strings.

CASO C) El usuario pone una cantidad de comida irreal (ej. 40 pasteles):
{ \"tipo_respuesta\": \"chat\", \"mensaje_respuesta\": \"[Pregúntale amigablemente si está seguro para comprobar que no hubo errores al escribir. Si dice que sí en otro mensaje, procesas como CASO B]\" }

CASO D) Temas ajenos a la dieta o nutrición:
{ \"tipo_respuesta\": \"chat\", \"mensaje_respuesta\": \"Lo siento, solo ayudo con comida y nutrición.\" }

CASO E) El usuario PIDIÓ CONSEJOS de qué alimento/receta COMER AHORA MISMO:
{ \"tipo_respuesta\": \"food_log\", \"alimento\": \"[Nombre Platillo Sugerido adaptado estrictamente a su PERFIL, META Y RESTRICCIONES]\", \"descripcion\": \"[Mini receta o justificación de por qué le sirve]\", \"calorias\": 350, \"proteina\": 25, \"carbs\": 40, \"grasas\": 10, \"tipo_comida\": \"Sugerencia\", \"tipo_icono\": \"solid\" }
ATENCIÓN: Los valores 350, 25, 40, 10 son solo ejemplos. Calcula valores REALES para el alimento sugerido.";

// 5. Construir memoria
$contents = construirContenidosIA($historial_js, $mensaje_usuario, $system_prompt, $perfil_texto, $imagen_b64, $audio_b64);

$payload = [
    "contents" => $contents,
    "generationConfig" => [
        "temperature" => 0.4
    ]
];

// 6. Ejecutar llamada cURL
$res_data = consultarApiIA($api_url, $payload);

// 7. Procesar y sanitizar respuesta de la IA
$resultado = sanitizarRespuestaIA($res_data['response'], $res_data['http_code']);

echo json_encode($resultado);
exit();


// ============================================================
// FUNCIONES AUXILIARES (Refactorizadas para mejorar complejidad)
// ============================================================

/**
 * Obtiene el perfil de base de datos y construye el texto descriptivo del contexto del usuario.
 */
function obtenerPerfilTexto($conn, int $usuario_id): string {
    $u_data = Usuario::getById($conn, $usuario_id);
    $restricciones_data = Usuario::getRestricciones($conn, $usuario_id);
    $restricciones = array_column($restricciones_data, 'nombre');

    $edad = "No definida";
    if ($u_data && !empty($u_data['fecha_nacimiento'])) {
        $edad = date_diff(date_create($u_data['fecha_nacimiento']), date_create('today'))->y;
    }
    
    $str_restricciones = empty($restricciones) ? "Ninguna" : implode(", ", $restricciones);
    $peso = $u_data['peso_kg'] ?? 'No definido';
    $sexo = $u_data['sexo'] ?? 'No definido';
    $meta = $u_data['meta_principal'] ?? 'No definida';

    return "CONTEXTO OBLIGATORIO DEL USUARIO ACTUAL:\n" .
           "- Edad: $edad años\n" .
           "- Peso: $peso kg\n" .
           "- Sexo: $sexo\n" .
           "- Meta Principal: $meta\n" .
           "- Restricciones Médicas/Dietas: $str_restricciones\n" .
           "ATENCIÓN: Basa todas tus recomendaciones, cálculos y charlas cordiales en este contexto.\n\n";
}

/**
 * Construye el historial de contenidos para enviar a la API de la IA.
 */
function construirContenidosIA(array $historial_js, string $mensaje_usuario, string $system_prompt, string $perfil_texto, ?string $imagen_b64, ?string $audio_b64): array {
    $contents = [];
    $primer_mensaje = true;

    // Si NO mandaron historial válido, lo forzamos con el primer turno
    if (empty($historial_js)) {
        $historial_js = [
            ["role" => "user", "parts" => [["text" => $mensaje_usuario]]]
        ];
    }

    foreach ($historial_js as $index => $msg) {
        $parts = [];
        $texto_limpio = $msg['parts'][0]['text'] ?? '';
        
        // Inyectamos el cerebro (Prompts + DB) secretamente bajo la alfombra en la primera interacción
        if ($primer_mensaje && ($msg['role'] ?? '') === 'user') {
            $texto_limpio = $system_prompt . "\n\n" . $perfil_texto . "\nAnaliza lo siguiente: " . $texto_limpio;
            $primer_mensaje = false;
        }

        $parts[] = ["text" => $texto_limpio];

        // Si es el último mensaje del usuario y hay imagen o audio adjunto, lo agregamos al turno actual
        if ($index === count($historial_js) - 1 && ($msg['role'] ?? '') === 'user') {
            // Adjuntar Imagen si existe
            if (!empty($imagen_b64)) {
                $raw_img_b64 = preg_replace('/^data:image\/\w+;base64,/', '', $imagen_b64);
                $parts[] = [
                    "inlineData" => [
                        "mimeType" => "image/jpeg",
                        "data" => $raw_img_b64
                    ]
                ];
            }

            // Adjuntar Audio si existe
            if (!empty($audio_b64)) {
                $raw_audio_b64 = preg_replace('/^data:audio\/\w+;base64,/', '', $audio_b64);
                $parts[] = [
                    "inlineData" => [
                        "mimeType" => "audio/mp3",
                        "data" => $raw_audio_b64
                    ]
                ];
            }
        }
        
        $contents[] = [
            "role" => $msg['role'] ?? 'user',
            "parts" => $parts
        ];
    }

    return $contents;
}

/**
 * Ejecuta la llamada a la API usando cURL.
 */
function consultarApiIA(string $api_url, array $payload): array {
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'response' => $response,
        'http_code' => $http_code
    ];
}

/**
 * Procesa y sanitiza la respuesta devuelta por la API.
 */
function sanitizarRespuestaIA($response, $http_code): array {
    if ($http_code !== 200) {
        return [
            'status' => 'error',
            'message' => "Fallo la conexión con Gemma. HTTP Code: $http_code"
        ];
    }

    $respuesta_api = json_decode($response, true);
    if (!isset($respuesta_api['candidates'][0]['content']['parts'][0]['text'])) {
        return [
            'status' => 'error',
            'message' => 'Respuesta inesperada de la API.'
        ];
    }

    $contenido_gemma = $respuesta_api['candidates'][0]['content']['parts'][0]['text'];
    $contenido_limpio = preg_replace('/```json|```/', '', $contenido_gemma);
    $json_final = json_decode(trim($contenido_limpio), true);

    if (!$json_final) {
        return [
            'status' => 'error',
            'message' => 'Gemma no devolvió un formato válido.'
        ];
    }

    // Sanitizar valores numéricos y campos de comida
    if (isset($json_final['tipo_respuesta']) && $json_final['tipo_respuesta'] === 'food_log') {
        $json_final['calorias'] = (int) preg_replace('/[^0-9.]/', '', (string)($json_final['calorias'] ?? 0));
        $json_final['proteina'] = (float) preg_replace('/[^0-9.]/', '', (string)($json_final['proteina'] ?? 0));
        $json_final['carbs']    = (float) preg_replace('/[^0-9.]/', '', (string)($json_final['carbs'] ?? 0));
        $json_final['grasas']   = (float) preg_replace('/[^0-9.]/', '', (string)($json_final['grasas'] ?? 0));
        
        $json_final['alimento'] = str_replace(["'", '"', '\\'], ['', '', ''], $json_final['alimento'] ?? 'Alimento');
        $json_final['descripcion'] = str_replace(["'", '"', '\\'], ['', '', ''], $json_final['descripcion'] ?? '');
        
        $tipos_validos = ['Desayuno', 'Comida', 'Cena', 'Snack', 'Almuerzo', 'Merienda', 'Sugerencia'];
        if (!in_array($json_final['tipo_comida'] ?? '', $tipos_validos)) {
            $json_final['tipo_comida'] = 'Snack';
        }
    }

    return [
        'status' => 'success',
        'food_data' => $json_final
    ];
}
?>