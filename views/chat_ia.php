<?php
require_once '../controllers/chat_ia_controller.php';

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
    </div>

    <div class="chat-messages" id="chat-box">
        
        <div class="date-badge">HOY, 2:30 PM</div>

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
        <div class="msg-wrapper ai" id="typing-msg" style="display: none;">
            <div class="msg-row">
                <div class="avatar bot-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle></svg>
                </div>
                <div class="bubble bot-bubble" style="padding: 1rem 1.2rem;">
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="chat-input-container">
        <div class="chat-input-row">
            <button class="btn-mic">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>
            </button>
            <div class="input-wrapper">
                <input type="text" id="user-input" class="chat-input" placeholder="Escribe o dicta tu comida..." autocomplete="off">
                <button class="btn-send" id="btn-send">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                </button>
            </div>
        </div>
        <div class="disclaimer">
            NutrIAssist puede cometer errores. Verifica la información.
        </div>
    </div>

</div>

<script src="../assets/js/chat_ia.js?v=<?= time() ?>"></script>
</body>
</html>