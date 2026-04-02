<?php
header('Content-Type: application/json');

$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);
$texto_hablar = $data['texto'] ?? '';

if (empty($texto_hablar)) {
    echo json_encode(['status' => 'error', 'message' => 'Sin texto']);
    exit();
}

require_once '../config/keys.php';
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODELO_TTS . ":generateContent?key=" . GEMINI_API_KEY;


// Solo le decimos que "Hable este texto"
$payload = json_encode([
    "contents" => [
        [
            "role" => "user",
            "parts" => [
                ["text" => "Lee el siguiente texto con un tono amigable, natural y entusiasta, propio de un nutriólogo carismático:\n\n" . $texto_hablar]
            ]
        ]
    ],
    "generationConfig" => [
        "responseModalities" => ["AUDIO"],
        "speechConfig" => [
            "voiceConfig" => [
                "prebuiltVoiceConfig" => [
                    "voiceName" => "Puck" // Una voz oficial de Gemini
                ]
            ]
        ]
    ]
]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$base64_audio = '';

if (isset($data['candidates'][0]['content']['parts'])) {
    foreach ($data['candidates'][0]['content']['parts'] as $part) {
        if (isset($part['inlineData']['data'])) {
            $base64_audio = $part['inlineData']['data'];
            break;
        }
    }
}

if (!empty($base64_audio)) {
    echo json_encode(['status' => 'success', 'audio_base64' => $base64_audio]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se generó audio']);
}
?>
