<?php
require_once '../utils/Auth.php';
$id_usuario = Auth::requireLogin();

$page_title = 'NutrIAssist - Chat Inteligente';
$extra_css  = '../assets/css/chat_ia.css';
require_once 'includes/header.php';
?>

<div class="mobile-container">
    
    <div class="chat-header">
        <button class="back-btn" onclick="window.history.back()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        </button>
        <h1>Asistente NutrIAssist</h1>
        <button id="btn-mute" title="Silenciar asistente" class="btn-mute">
            <svg id="mute-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path id="vol-waves" d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
        </button>
    </div>

    <div class="chat-messages" id="chat-box">
        
        <div class="msg-wrapper ai" id="welcome-msg">
            <div class="msg-label">NutrIAssist</div>
            <div class="msg-row">
                <div class="avatar bot-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path><line x1="8" y1="16" x2="8" y2="16"></line><line x1="16" y1="16" x2="16" y2="16"></line></svg>
                </div>
                <div class="bubble bot-bubble">
                    ¿Qué tal tu tarde? ¿Qué registrarás ahora?
                </div>
            </div>
        </div>
        
        <!-- Simulador de estado 'escribiendo' -->
        <div class="msg-wrapper ai hidden" id="typing-msg">
            <div class="msg-row">
                <div class="avatar bot-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle></svg>
                </div>
                <div class="bubble bot-bubble bubble--typing">
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="chat-input-container">
        <div class="chat-input-row">
            <button class="btn-action" id="btn-camera" title="Tomar Foto">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
            </button>
            <button class="btn-action" id="btn-gallery" title="Galería">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            </button>
            <div class="input-wrapper">
                <input type="text" id="user-input" class="chat-input" placeholder="Comida o sube foto..." autocomplete="off">
                <button class="btn-send" id="btn-send">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                </button>
            </div>
            <!-- Inputs ocultos para archivos -->
            <input type="file" id="input-camera" accept="image/*" capture="environment" class="hidden">
            <input type="file" id="input-gallery" accept="image/*" class="hidden">
        </div>
        <!-- Previsualización de imagen -->
        <div id="image-preview-container" class="preview-container hidden">
            <img id="image-preview" src="" class="preview-img">
            <button id="remove-image" class="btn-remove-preview">×</button>
        </div>
        <div class="disclaimer">
            NutrIAssist puede cometer errores. Verifica la información.
        </div>
    </div>

</div>

<script src="../assets/js/chat_ia.js?v=<?= time() ?>"></script>
</body>
</html>
