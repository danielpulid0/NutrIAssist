<?php
session_start();
header('Content-Type: application/json');

// 1. Validar seguridad e incluir BD
require_once '../config/conexion.php';
if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// --- OBTENER PERFIL DE BASE DE DATOS ---
$stmt = $conn->prepare("SELECT peso_kg, altura_cm, sexo, fecha_nacimiento, meta_principal FROM Usuarios WHERE id_usuario = ?");
$stmt->execute([$usuario_id]);
$u_data = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt_r = $conn->prepare("SELECT r.nombre FROM Restricciones_Medicas r INNER JOIN Usuario_Restriccion ur ON r.id_restriccion = ur.id_restriccion WHERE ur.id_usuario = ?");
$stmt_r->execute([$usuario_id]);
$restricciones = $stmt_r->fetchAll(PDO::FETCH_COLUMN);

$edad = "No definida";
if ($u_data && $u_data['fecha_nacimiento']) {
    $edad = date_diff(date_create($u_data['fecha_nacimiento']), date_create('today'))->y;
}
$str_restricciones = empty($restricciones) ? "Ninguna" : implode(", ", $restricciones);
$peso = $u_data['peso_kg'] ?? 'No definido';
$sexo = $u_data['sexo'] ?? 'No definido';
$meta = $u_data['meta_principal'] ?? 'No definida';

$perfil_texto = "CONTEXTO OBLIGATORIO DEL USUARIO ACTUAL:\n- Edad: $edad años\n- Peso: $peso kg\n- Sexo: $sexo\n- Meta Principal: $meta\n- Restricciones Médicas/Dietas: $str_restricciones\nATENCIÓN: Basa todas tus recomendaciones, cálculos y charlas cordiales en este contexto.\n\n";

// 34. Leer JSON del Frontend
$json_input = file_get_contents('php://input');
$dataJson = json_decode($json_input, true);
$mensaje_usuario = $dataJson['prompt'] ?? '';
$historial_js    = $dataJson['history'] ?? [];
$imagen_base64   = $dataJson['image'] ?? null;

if (empty($mensaje_usuario) && empty($imagen_base64)) {
    echo json_encode(['status' => 'error', 'message' => 'El mensaje o imagen están vacíos']);
    exit();
}

// ==========================================
require_once '../config/keys.php';

// CAMBIO AUTOMÁTICO: Si hay imagen, forzamos modelo multimodal (Gemini Flash)
// Si solo es texto, usamos el modelo por defecto definido en keys (ej. Gemma)
$modelo = $imagen_base64 ? 'gemini-1.5-flash' : GEMINI_MODELO_CHAT;

$api_url = "https://generativelanguage.googleapis.com/v1beta/models/" . $modelo . ":generateContent?key=" . GEMINI_API_KEY;


// 3. SYSTEM PROMPT (Igual que antes)
$system_prompt = "Eres NutrIAssist, un asistente nutricional en México.
REGLA ABSOLUTA: Responde SIEMPRE con un objeto JSON válido.
Si recibes una imagen, actúa como CASO B (food_log) calculando macros estimados.

Estructuras JSON:
CASO A) Charla: { \"tipo_respuesta\": \"chat\", \"mensaje_respuesta\": \"...\" }
CASO B) Registro: { \"tipo_respuesta\": \"food_log\", \"alimento\": \"...\", \"descripcion\": \"...\", \"calorias\": 0, \"proteina\": 0, \"carbs\": 0, \"grasas\": 0, \"tipo_comida\": \"...\", \"tipo_icono\": \"solid\" }";

// 4. CONSTRUIR MEMORIA
$contents = [];

// Si hay historial previo, lo agregamos (solo texto por simplicidad de memoria)
foreach ($historial_js as $msg) {
    if ($msg['role'] === 'user') {
        $contents[] = [
            "role" => "user",
            "parts" => [["text" => $msg['parts'][0]['text']]]
        ];
    } else {
        $contents[] = [
            "role" => "model",
            "parts" => [["text" => $msg['parts'][0]['text']]]
        ];
    }
}

// AGREGAR EL TURNO ACTUAL (Puede llevar imagen o solo texto)
$current_parts = [];
if ($imagen_base64) {
    $current_parts[] = [
        "inline_data" => [
            "mime_type" => "image/jpeg",
            "data" => $imagen_base64
        ]
    ];
}

$prompt_final = $system_prompt . "\n\n" . $perfil_texto . "\nAnaliza lo siguiente: " . $mensaje_usuario;
$current_parts[] = ["text" => $prompt_final];

$contents[] = [
    "role" => "user",
    "parts" => $current_parts
];

$payload = json_encode([
    "contents" => $contents,
    "generationConfig" => [
        "temperature" => 0.3
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