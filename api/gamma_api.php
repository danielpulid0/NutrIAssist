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

// --- OBTENER PERFIL DE BASE DE DATOS ---
$u_data = Usuario::getById($conn, $usuario_id);

$restricciones_data = Usuario::getRestricciones($conn, $usuario_id);
$restricciones = array_column($restricciones_data, 'nombre');

$edad = "No definida";
if ($u_data && $u_data['fecha_nacimiento']) {
    $edad = date_diff(date_create($u_data['fecha_nacimiento']), date_create('today'))->y;
}
$str_restricciones = empty($restricciones) ? "Ninguna" : implode(", ", $restricciones);
$peso = $u_data['peso_kg'] ?? 'No definido';
$sexo = $u_data['sexo'] ?? 'No definido';
$meta = $u_data['meta_principal'] ?? 'No definida';

$perfil_texto = "CONTEXTO OBLIGATORIO DEL USUARIO ACTUAL:\n- Edad: $edad años\n- Peso: $peso kg\n- Sexo: $sexo\n- Meta Principal: $meta\n- Restricciones Médicas/Dietas: $str_restricciones\nATENCIÓN: Basa todas tus recomendaciones, cálculos y charlas cordiales en este contexto.\n\n";

// 2. Leer JSON del Frontend
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);
$mensaje_usuario = $data['prompt'] ?? '';
$historial_js = $data['history'] ?? [];
$imagen_b64 = $data['image'] ?? null;
$audio_b64 = $data['audio'] ?? null; // Audio capturado

if (empty($mensaje_usuario) && empty($imagen_b64) && empty($audio_b64)) {
    echo json_encode(['status' => 'error', 'message' => 'El mensaje está vacío']);
    exit();
}

// ==========================================
// 6. Configurar el endpoint dinámico (IA Híbrida)
$modelo_usar = !empty($audio_b64) ? GEMINI_MODELO_VOZ : GEMINI_MODELO_CHAT;
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo_usar}:generateContent?key=" . GEMINI_API_KEY;


// 3. SYSTEM PROMPT
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

// 4. CONSTRUIR MEMORIA (Contexto + Historial)
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
    $texto_limpio = $msg['parts'][0]['text'];
    
    // Inyectamos el cerebro (Prompts + DB) secretamente bajo la alfombra en la primera interacción
    if ($primer_mensaje && $msg['role'] === 'user') {
        $texto_limpio = $system_prompt . "\n\n" . $perfil_texto . "\nAnaliza lo siguiente: " . $texto_limpio;
        $primer_mensaje = false;
    }

    $parts[] = ["text" => $texto_limpio];

    // Si es el último mensaje del usuario y hay imagen o audio adjunto, lo agregamos al turno actual para Gemini/Gemma
    if ($index === count($historial_js) - 1 && $msg['role'] === 'user') {
        
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
        "role" => $msg['role'],
        "parts" => $parts
    ];
}

$payload = json_encode([
    "contents" => $contents,
    "generationConfig" => [
        "temperature" => 0.4
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
            // ── Sanitizar valores numéricos si la IA devolvió strings ──
            if (isset($json_final['tipo_respuesta']) && $json_final['tipo_respuesta'] === 'food_log') {
                // Forzar valores numéricos puros (la IA a veces devuelve "350 kcal" o "25g")
                $json_final['calorias'] = (int) preg_replace('/[^0-9.]/', '', (string)($json_final['calorias'] ?? 0));
                $json_final['proteina'] = (float) preg_replace('/[^0-9.]/', '', (string)($json_final['proteina'] ?? 0));
                $json_final['carbs']    = (float) preg_replace('/[^0-9.]/', '', (string)($json_final['carbs'] ?? 0));
                $json_final['grasas']   = (float) preg_replace('/[^0-9.]/', '', (string)($json_final['grasas'] ?? 0));
                
                // Sanitizar nombre de alimento: quitar comillas simples/dobles que rompen onclick HTML
                $json_final['alimento'] = str_replace(["'", '"', '\\'], ['', '', ''], $json_final['alimento'] ?? 'Alimento');
                $json_final['descripcion'] = str_replace(["'", '"', '\\'], ['', '', ''], $json_final['descripcion'] ?? '');
                
                // Asegurar que tipo_comida sea un valor válido
                $tipos_validos = ['Desayuno', 'Comida', 'Cena', 'Snack', 'Almuerzo', 'Merienda', 'Sugerencia'];
                if (!in_array($json_final['tipo_comida'] ?? '', $tipos_validos)) {
                    $json_final['tipo_comida'] = 'Snack';
                }
            }
            
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