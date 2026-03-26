<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Chat Inteligente</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #E7EBEE; /* Fondo gris claro */
            /* global.css flex centering is preserved */
        }
        
        .mobile-container {
            padding: 0;
            height: 100vh;
            background-color: #E7EBEE; /* Override background for chat */
        }

        .chat-header {
            background-color: #FFFFFF;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #D1D5DB;
            z-index: 10;
        }

        .chat-header .back-btn {
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            cursor: pointer;
            color: #000;
            display: flex;
            align-items: center;
        }

        .chat-header h1 { 
            font-size: 1.15rem; 
            margin: 0; 
            flex-grow: 1; 
            text-align: center; 
            font-weight: 700;
            padding-right: 24px; /* Para equilibrar el back-btn */
        }

        .chat-messages {
            flex-grow: 1;
            padding: 1.25rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            padding-bottom: 150px; /* Espacio para el input */
        }

        .date-badge {
            align-self: center;
            background-color: #DBE0E8;
            color: #64748B;
            font-size: 0.70rem;
            font-weight: 700;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        /* Message Wrappers */
        .msg-wrapper {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .msg-wrapper.ai { align-items: flex-start; }
        .msg-wrapper.user { align-items: flex-end; }

        .msg-label {
            font-size: 0.75rem;
            color: #94A3B8;
            margin-bottom: 0.3rem;
        }
        
        .msg-wrapper.ai .msg-label { margin-left: 3.2rem; }
        .msg-wrapper.user .msg-label { margin-right: 3.2rem; }

        .msg-row {
            display: flex;
            gap: 0.5rem;
            max-width: 90%;
            align-items: flex-start;
        }

        .msg-wrapper.ai .msg-row { flex-direction: row; }
        .msg-wrapper.user .msg-row { flex-direction: row-reverse; }

        /* Avatares */
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-shrink: 0;
            margin-top: 1rem;
        }
        
        .avatar.bot-icon {
            background-color: #1DF157;
            color: black;
        }

        .avatar.user-icon {
            background-color: #CBD5E1; 
            border: 2px solid white;
        }
        .avatar.user-icon img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Burbujas */
        .bubble {
            padding: 1.1rem;
            font-size: 0.95rem;
            line-height: 1.4;
            color: #111827;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            animation: fadeIn 0.3s ease;
        }

        .bubble.bot-bubble {
            background-color: #FFFFFF;
            border-radius: 4px 16px 16px 16px;
        }

        .bubble.user-bubble {
            background-color: #1DF157;
            border-radius: 16px 4px 16px 16px;
            font-weight: 500;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Tarjeta de Confirmación de Alimentos */
        .food-card {
            background-color: #FFFFFF;
            border-radius: 16px;
            overflow: hidden;
            width: 280px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            animation: fadeIn 0.4s ease;
        }

        .fc-banner {
            position: relative;
            height: 140px;
            background-image: url('../assets/img/tacos_asada.png');
            background-size: cover;
            background-position: center;
        }
        
        .fc-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.1) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1rem;
        }

        .fc-banner h3 { color: white; font-size: 1.1rem; margin: 0 0 0.2rem 0; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.5);}
        .fc-banner p { color: #E2E8F0; font-size: 0.75rem; margin: 0; text-shadow: 0 1px 2px rgba(0,0,0,0.5);}

        .fc-badge {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background-color: #1DF157;
            color: #000;
            font-weight: 800;
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
        }

        .fc-body { padding: 1rem; }

        .fc-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .fc-item:last-child {
            margin-bottom: 0;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 1rem;
        }

        .fc-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 0.8rem;
        }

        .fc-icon.solid { background-color: #FFEDD5; color: #EA580C; }
        .fc-icon.liquid { background-color: #DBEAFE; color: #2563EB; }

        .fc-item-info { flex-grow: 1; }
        .fc-item-info h4 { font-size: 0.9rem; margin: 0; color: #0F172A; font-weight: 600; }
        .fc-item-info p { font-size: 0.75rem; margin: 0.1rem 0 0 0; color: #64748B; }

        .fc-item-cal {
            text-align: right;
            font-weight: 700;
            font-size: 0.95rem;
            color: #1E293B;
        }
        .fc-item-cal span { display: block; font-size: 0.7rem; color: #475569; font-weight: 500; }

        .fc-actions { display: flex; gap: 0.5rem; padding: 0 1rem 1rem 1rem; }
        
        .fc-btn {
            flex: 1;
            padding: 0.75rem 0;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            text-align: center;
            border: none;
        }

        .fc-btn.outline {
            background-color: #F8FAFC;
            border: 1px solid #CBD5E1;
            color: #334155;
        }

        .fc-btn.primary {
            background-color: #1DF157;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }

        /* Footer Input Area */
        .chat-input-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #FFFFFF;
            padding: 0.75rem 1rem 1.5rem 1rem;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.03);
            z-index: 100;
            box-sizing: border-box;
        }

        .chat-input-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.6rem;
        }

        .btn-mic {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #F1F5F9;
            border: none;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            color: #475569;
            flex-shrink: 0;
        }

        .chat-input {
            flex-grow: 1;
            height: 44px;
            border: 1px solid #CBD5E1;
            background-color: #F8FAFC;
            border-radius: 22px;
            padding: 0 1rem;
            font-size: 0.9rem;
            outline: none;
            color: #0F172A;
            width: 100%;
            box-sizing: border-box;
        }

        .chat-input:focus { border-color: #1DF157; background-color: #FFFFFF; }

        .btn-send {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #1DF157;
            border: none;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            color: #000;
            flex-shrink: 0;
            margin-left: -46px; /* Para superponerlo dentro del input */
            margin-top: 4px;
            margin-bottom: 4px;
        }
        
        /* Ajuste: Para que el layout superpuesto funcione, metemos el input en un wrapper */
        .input-wrapper {
            position: relative;
            flex-grow: 1;
            display: flex;
        }
        .input-wrapper .chat-input { padding-right: 48px; }
        .input-wrapper .btn-send { position: absolute; right: 4px; top: 4px; }

        .disclaimer {
            text-align: center;
            font-size: 0.65rem;
            color: #94A3B8;
        }

        /* Escribiendo indicador animado */
        .typing-indicator { display: flex; align-items: center; gap: 4px; height: 100%; }
        .typing-indicator span { display: inline-block; width: 6px; height: 6px; background-color: #94A3B8; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
    </style>
</head>
<body>

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
    <script>
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');
        const btnSend = document.getElementById('btn-send');
        const typingMsg = document.getElementById('typing-msg');

        async function sendMessage() {
            const text = userInput.value.trim();
            if (!text) return;

            // 1. Mostrar mensaje del usuario localmente
            appendUserMessage(text);
            userInput.value = '';
            
            // 2. Mostrar indicador "escribiendo..." de la IA
            chatBox.appendChild(typingMsg);
            typingMsg.style.display = 'flex';
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                // 3. Petición POST a la API de Gemma (Backend en PHP)
                const response = await fetch('../controllers/gamma_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: text })
                });

                const data = await response.json();
                
                // Ocultamos el indicador de typing
                typingMsg.style.display = 'none';

                if (data.status === 'success' && data.food_data) {
                    const iaResponse = data.food_data;
                    
                    if (iaResponse.tipo_respuesta === 'chat') {
                        // Modo Conversación
                        appendBotText(iaResponse.mensaje_respuesta);
                    } else if (iaResponse.tipo_respuesta === 'food_log') {
                        // Modo Registro de Comida
                        renderBotCard(iaResponse);
                    }
                } else {
                    appendBotText("Lo siento, tuve un problema analizando eso. ¿Puedes repetirlo?");
                }

            } catch (error) {
                typingMsg.style.display = 'none';
                appendBotText("Error de red. Asegúrate de tener conexión.");
            }
        }

        function appendUserMessage(text) {
            const html = `
            <div class="msg-wrapper user">
                <div class="msg-label">Tú</div>
                <div class="msg-row">
                    <div class="bubble user-bubble">${text}</div>
                    <div class="avatar user-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-top:2px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                </div>
            </div>`;
            const div = document.createElement('div');
            div.innerHTML = html;
            chatBox.insertBefore(div.firstElementChild, typingMsg);
            chatBox.scroll({ top: chatBox.scrollHeight, behavior: 'smooth' });
        }
        
        function appendBotText(text) {
            const html = `
            <div class="msg-wrapper ai">
                <div class="msg-label">NutrIAssist</div>
                <div class="msg-row">
                    <div class="avatar bot-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
                    </div>
                    <div class="bubble bot-bubble">${text}</div>
                </div>
            </div>`;
            const div = document.createElement('div');
            div.innerHTML = html;
            chatBox.insertBefore(div.firstElementChild, typingMsg);
            chatBox.scroll({ top: chatBox.scrollHeight, behavior: 'smooth' });
        }

        function renderBotCard(data) {
            const templateId = 'card_' + Date.now();
            // Evitar nulos
            const calorias = data.calorias || 0;
            const alimento = data.alimento || "Alimento desconocido";
            const detalle = data.descripcion || "Porción regular";
            const comida = data.tipo_comida || "Comida";
            const proteina = data.proteina || 0;
            const carbs = data.carbs || 0;
            const grasas = data.grasas || 0;

            const iconClass = data.tipo_icono === 'liquid' ? 'liquid' : 'solid';
            const iconSvg = iconClass === 'liquid' 
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 22h8"></path><path d="M12 2v20"></path><path d="M16 8l-4 4-4-4"></path><path d="M12 12V2"></path></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"></path><path d="M7 2v20"></path><path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"></path></svg>';

            // Estructura stringificada para el botón
            const jsonPayload = JSON.stringify({ calorias, proteina, carbs, grasas }).replace(/"/g, '&quot;');

            const html = `
            <div class="msg-wrapper ai" id="${templateId}">
                <div class="msg-label">NutrIAssist</div>
                <div class="msg-row">
                    <div class="avatar bot-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
                    </div>
                    <div class="food-card">
                        <div class="fc-banner" style="background-image: url('https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=400&h=140&fit=crop');">
                            <div class="fc-banner-overlay">
                                <h3>Confirmación de Registro</h3>
                                <p>${comida} • ${calorias} kcal total</p>
                            </div>
                            <div class="fc-badge">+${calorias} kcal</div>
                        </div>
                        <div class="fc-body">
                            <div class="fc-item">
                                <div class="fc-icon ${iconClass}">
                                    ${iconSvg}
                                </div>
                                <div class="fc-item-info">
                                    <h4>${alimento}</h4>
                                    <p>${detalle}</p>
                                </div>
                                <div class="fc-item-cal">
                                    ${calorias}<span>kcal</span>
                                </div>
                            </div>
                        </div>

                        <div class="fc-actions">
                            <button class="fc-btn outline" onclick="editFoodData('${templateId}', ${calorias}, ${proteina}, ${carbs}, ${grasas})">Editar Detalles</button>
                            <button class="fc-btn primary confirm-btn" onclick="saveFoodData('${templateId}', '${jsonPayload}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
            
            const div = document.createElement('div');
            div.innerHTML = html;
            chatBox.insertBefore(div.firstElementChild, typingMsg);
            chatBox.scroll({ top: chatBox.scrollHeight, behavior: 'smooth' });
        }

        function editFoodData(templateId, c, p, cb, g) {
            const card = document.getElementById(templateId);
            if(card) {
                const body = card.querySelector('.fc-body');
                body.innerHTML = `
                    <div style="padding: 1rem; background-color: #F8FAFC; border-bottom: 1px solid #E2E8F0;">
                        <h4 style="margin-top:0; font-size: 0.9rem; color: #0F172A;">Edición Manual de Macros</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; font-size: 0.85rem; color: #475569;">
                            <div style="display:flex; justify-content:space-between; align-items:center;"><label>Calorías:</label> <input type="number" id="e_cal_${templateId}" value="${c}" style="width: 50px; padding: 0.2rem; border: 1px solid #CBD5E1; border-radius:4px;"></div>
                            <div style="display:flex; justify-content:space-between; align-items:center;"><label>Proteína:</label> <input type="number" id="e_pro_${templateId}" value="${p}" style="width: 50px; padding: 0.2rem; border: 1px solid #CBD5E1; border-radius:4px;"></div>
                            <div style="display:flex; justify-content:space-between; align-items:center;"><label>Carbs:</label> <input type="number" id="e_car_${templateId}" value="${cb}" style="width: 50px; padding: 0.2rem; border: 1px solid #CBD5E1; border-radius:4px;"></div>
                            <div style="display:flex; justify-content:space-between; align-items:center;"><label>Grasas:</label> <input type="number" id="e_fat_${templateId}" value="${g}" style="width: 50px; padding: 0.2rem; border: 1px solid #CBD5E1; border-radius:4px;"></div>
                        </div>
                    </div>
                `;
                const action = card.querySelector('.fc-actions');
                action.innerHTML = `<button class="fc-btn primary" onclick="saveEditData('${templateId}')">✅ Hecho</button>`;
            }
        }

        function saveEditData(templateId) {
            const c = document.getElementById('e_cal_'+templateId).value || 0;
            const p = document.getElementById('e_pro_'+templateId).value || 0;
            const cb = document.getElementById('e_car_'+templateId).value || 0;
            const f = document.getElementById('e_fat_'+templateId).value || 0;
            
            // Reconstruir Payload JSON
            const jsonStr = JSON.stringify({calorias: c, proteina: p, carbs: cb, grasas: f}).replace(/"/g, '&quot;');
            
            // Retornar la visualización
            const body = document.querySelector(`#${templateId} .fc-body`);
            body.innerHTML = `
                <div style="padding: 1rem; text-align:center;">
                    <span style="color:#15803D; font-weight:700; font-size:0.85rem;">Valores actualizados listos</span><br>
                    <p style="font-size:0.8rem; color:#475569; margin-top:0.4rem;">Calorías: <b>${c}</b> • Proteína: <b>${p}g</b> • Carbs: <b>${cb}g</b> • Grasas: <b>${f}g</b></p>
                </div>
            `;
            
            const action = document.querySelector(`#${templateId} .fc-actions`);
            action.innerHTML = `
                <button class="fc-btn outline" onclick="editFoodData('${templateId}', ${c}, ${p}, ${cb}, ${f})">Editar</button>
                <button class="fc-btn primary confirm-btn" onclick="saveFoodData('${templateId}', '${jsonStr}')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Confirmar
                </button>
            `;
        }

        async function saveFoodData(templateId, jsonDataStr) {
            const btn = document.querySelector(`#${templateId} .confirm-btn`);
            btn.innerHTML = 'Guardando...';
            btn.style.backgroundColor = '#CBD5E1';
            btn.style.color = '#334155';
            btn.disabled = true;

            try {
                const response = await fetch('../controllers/guardar_comida_ia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: jsonDataStr
                });

                if (response.ok) {
                    const actions = document.querySelector(`#${templateId} .fc-actions`);
                    actions.innerHTML = '<div style="width: 100%; text-align: center; color: #15803D; font-weight: 700; padding: 0.5rem 0; font-size: 0.9rem;">✅ Guardado correctamente</div>';
                }
            } catch (e) {
                btn.innerHTML = 'Error';
            }
        }

        btnSend.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') sendMessage();
        });
    </script>
</body>
</html>