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

// 2. Leer JSON del Frontend
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);
$mensaje_usuario = $data['prompt'] ?? '';
$historial_js = $data['history'] ?? [];

if (empty($mensaje_usuario)) {
    echo json_encode(['status' => 'error', 'message' => 'El mensaje está vacío']);
    exit();
}

// ==========================================
require_once '../config/keys.php';
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODELO_CHAT . ":generateContent?key=" . GEMINI_API_KEY;


// 3. SYSTEM PROMPT
$system_prompt = "Eres NutrIAssist, un inteligente asistente nutricional creado para chatear, analizar y recomendar comida.
REGLA ABSOLUTA: Responde SIEMPRE con un objeto JSON válido.
Estructuras JSON permitidas según el Caso:

CASO A) Saludo o charla general:
{ \"tipo_respuesta\": \"chat\", \"mensaje_respuesta\": \"[Tu respuesta amigable y natural aquí]\" }

CASO B) El usuario reporta una comida explícitamente consumida:
{ \"tipo_respuesta\": \"food_log\", \"alimento\": \"[Deduce nombre]\", \"descripcion\": \"[Porción]\", \"calorias\": 0, \"proteina\": 0, \"carbs\": 0, \"grasas\": 0, \"tipo_comida\": \"Comida\", \"tipo_icono\": \"solid\" }

CASO C) El usuario pone una cantidad de comida irreal (ej. 40 pasteles):
{ \"tipo_respuesta\": \"chat\", \"mensaje_respuesta\": \"[Pregúntale amigablemente si está seguro para comprobar que no hubo errores al escribir. Si dice que sí en otro mensaje, procesas como CASO B]\" }

CASO D) Temas ajenos a la dieta o nutrición:
{ \"tipo_respuesta\": \"chat\", \"mensaje_respuesta\": \"Lo siento, solo ayudo con comida y nutrición.\" }

CASO E) El usuario PIDIÓ CONSEJOS de qué alimento/receta COMER AHORA MISMO:
{ \"tipo_respuesta\": \"food_log\", \"alimento\": \"[Nombre Platillo Sugerido adaptado estrictamente a su PERFIL, META Y RESTRICCIONES]\", \"descripcion\": \"[Mini receta o justificación de por qué le sirve]\", \"calorias\": 0, \"proteina\": 0, \"carbs\": 0, \"grasas\": 0, \"tipo_comida\": \"Sugerencia\", \"tipo_icono\": \"solid\" }";

// 4. CONSTRUIR MEMORIA (Contexto + Historial)
$contents = [];
$primer_mensaje = true;

// Si NO mandaron historial válido, lo forzamos con el primer turno
if (empty($historial_js)) {
    $historial_js = [
        ["role" => "user", "parts" => [["text" => $mensaje_usuario]]]
    ];
}

foreach ($historial_js as $msg) {
    $texto_limpio = $msg['parts'][0]['text'];
    
    // Inyectamos el cerebro (Prompts + DB) secretamente bajo la alfombra en la primera interacción
    if ($primer_mensaje && $msg['role'] === 'user') {
        $texto_limpio = $system_prompt . "\n\n" . $perfil_texto . "\nAnaliza lo siguiente: " . $texto_limpio;
        $primer_mensaje = false;
    }
    
    $contents[] = [
        "role" => $msg['role'],
        "parts" => [["text" => $texto_limpio]]
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