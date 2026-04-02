<?php
// ============================================================
// controllers/ia_swap.php — v2
// Devuelve 2-3 opciones de sustitución con % similitud,
// flag de recomendado, y nota comparativa.
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit();
}

$input       = json_decode(file_get_contents('php://input'), true);
$ingrediente = htmlspecialchars(trim($input['ingrediente'] ?? ''));
$gramos      = (float) ($input['gramos'] ?? 100);

if (empty($ingrediente) || $gramos <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit();
}

require_once '../config/conexion.php';
require_once '../config/keys.php';

// ─── Restricciones médicas del usuario ────────────────────────────────
$restricciones = [];
try {
    $stmt = $conn->prepare("
        SELECT rm.nombre FROM Restricciones_Medicas rm
        INNER JOIN Usuario_Restriccion ur ON rm.id_restriccion = ur.id_restriccion
        WHERE ur.id_usuario = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $restricciones = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('[ia_swap] DB: ' . $e->getMessage());
}

$str_restricciones = empty($restricciones) ? 'Ninguna' : implode(', ', $restricciones);

// ─── Subtítulo dinámico según restricciones ───────────────────────────
$subtitulo = empty($restricciones)
    ? "Sugerencias basadas en tus macros:"
    : "Sugerencias sin " . implode(' ni ', $restricciones) . " basadas en tus macros:";

// ─── Prompt para Gemma ────────────────────────────────────────────────
$prompt = <<<PROMPT
Eres un nutriólogo experto en México. El usuario quiere sustituir un ingrediente.

INGREDIENTE: {$ingrediente}
CANTIDAD: {$gramos}g
RESTRICCIONES: {$str_restricciones}

Devuelve EXACTAMENTE este JSON (sin markdown, sin texto extra):
{
  "opciones": [
    {
      "nombre": "[mejor sustituto]",
      "similitud": 95,
      "nota": "95% similitud de macros",
      "recomendado": true,
      "calorias": 0,
      "proteina": 0.0,
      "carbs": 0.0,
      "grasas": 0.0
    },
    {
      "nombre": "[segundo sustituto]",
      "similitud": 70,
      "nota": "70% similitud · Más alto en grasas",
      "recomendado": false,
      "calorias": 0,
      "proteina": 0.0,
      "carbs": 0.0,
      "grasas": 0.0
    }
  ]
}

REGLAS:
- Genera exactamente 2 opciones (pueden ser 3 si hay una opción muy diferente pero válida)
- similitud es un entero 0-100 que estima qué tan parecidos son los macros al original
- La primera opción debe ser la MÁS similar y tener recomendado: true
- Los macros deben estar calculados para {$gramos}g de porción, NO por 100g
- NO violes las restricciones médicas
- Los alimentos deben ser comunes en México
- La nota debe ser breve (máx 6 palabras) y descriptiva
PROMPT;

$api_url = "https://generativelanguage.googleapis.com/v1beta/models/"
         . GEMINI_MODELO_CHAT . ":generateContent?key=" . GEMINI_API_KEY;

$payload = json_encode([
    "contents"         => [["role" => "user", "parts" => [["text" => $prompt]]]],
    "generationConfig" => ["temperature" => 0.2, "maxOutputTokens" => 400],
]);

$ch = curl_init($api_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode(['status' => 'error', 'message' => 'Error al contactar con la IA.']);
    exit();
}

$api_data = json_decode($response, true);
$raw_text = $api_data['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Limpiar markdown residual
$clean     = trim(preg_replace('/```json|```/i', '', $raw_text));
$resultado = json_decode($clean, true);

if (!$resultado || empty($resultado['opciones'])) {
    error_log('[ia_swap] Respuesta inesperada: ' . $raw_text);
    echo json_encode(['status' => 'error', 'message' => 'La IA no devolvió sustituciones válidas.']);
    exit();
}

// Sanear cada opción
foreach ($resultado['opciones'] as &$op) {
    $op['nombre']    = htmlspecialchars(strip_tags($op['nombre'] ?? ''));
    $op['nota']      = htmlspecialchars(strip_tags($op['nota']   ?? ''));
    $op['similitud'] = max(0, min(100, (int)($op['similitud'] ?? 0)));
    $op['recomendado'] = (bool) ($op['recomendado'] ?? false);
    $op['calorias']  = (int)   ($op['calorias'] ?? 0);
    $op['proteina']  = (float) ($op['proteina'] ?? 0);
    $op['carbs']     = (float) ($op['carbs']    ?? 0);
    $op['grasas']    = (float) ($op['grasas']   ?? 0);
}
unset($op);

echo json_encode([
    'status'    => 'success',
    'subtitulo' => $subtitulo,
    'data'      => $resultado['opciones'],
]);
?>
