<?php
// Controlador para Transcribir Audio (Speech-to-Text) Post-Audio
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin(true);
Auth::requirePost();

require_once '../config/keys.php';

// 1. Leer Audio Base64 del Frontend
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);
$audio_b64 = $data['audio'] ?? null;
$mime_type  = $data['mimeType'] ?? 'audio/webm'; // Formato real del navegador

if (empty($audio_b64)) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió audio para transcribir']);
    exit();
}

// 2. Limpiar prefijo base64 (acepta cualquier tipo de audio)
$raw_audio_b64 = preg_replace('/^data:audio\/[\w;]+;base64,/', '', $audio_b64);

// 3. Llamar a la API de Gemini para transcripción
// Usamos gemini-1.5-flash que es excelente y barato para transcribir audio
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . GEMINI_API_KEY;

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Transcribe este audio exactamente como se escucha, sin añadir comentarios ni saludos. Solo devuelve el texto transcrito."],
                [
                    "inlineData" => [
                        "mimeType" => $mime_type,
                        "data" => $raw_audio_b64
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    $res_api = json_decode($response, true);
    $texto_transcrito = $res_api['candidates'][0]['content']['parts'][0]['text'] ?? "Audio sin voz detected";
    
    echo json_encode([
        'status' => 'success',
        'transcripcion' => trim($texto_transcrito)
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error en la transcripción remota',
        'debug' => $response
    ]);
}
?>
