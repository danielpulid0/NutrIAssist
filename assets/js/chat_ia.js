const chatBox = document.getElementById('chat-box');
const userInput = document.getElementById('user-input');
const btnSend = document.getElementById('btn-send');
const typingMsg = document.getElementById('typing-msg');

let chatHistory = [];
let selectedImageBase64 = null;

// Elementos de la UI de imagen
const btnCamera = document.getElementById('btn-camera');
const btnGallery = document.getElementById('btn-gallery');
const inputCamera = document.getElementById('input-camera');
const inputGallery = document.getElementById('input-gallery');
const previewContainer = document.getElementById('image-preview-container');
const previewImg = document.getElementById('image-preview');
const btnRemoveImg = document.getElementById('remove-image');

// Eventos para abrir selectores de archivos
btnCamera.addEventListener('click', () => inputCamera.click());
btnGallery.addEventListener('click', () => inputGallery.click());

// Manejar selección de imagen
inputCamera.addEventListener('change', (e) => handleImageSelect(e.target.files[0]));
inputGallery.addEventListener('change', (e) => handleImageSelect(e.target.files[0]));

function handleImageSelect(file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (event) => {
        selectedImageBase64 = event.target.result;
        previewImg.src = selectedImageBase64;
        previewContainer.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

// Quitar imagen
btnRemoveImg.addEventListener('click', () => {
    selectedImageBase64 = null;
    previewContainer.style.display = 'none';
    inputCamera.value = '';
    inputGallery.value = '';
});

async function sendMessage() {
    const text = userInput.value.trim();
    
    // Si no hay texto NI imagen, no enviamos nada
    if (!text && !selectedImageBase64) return;

    // Almacenar en la memoria de la UI
    chatHistory.push({ role: "user", parts: [{ text: text }] });

    // 1. Mostrar mensaje del usuario localmente (con imagen si existe)
    appendUserMessage(text, selectedImageBase64);
    
    const sendData = { 
        prompt: text || "Analiza esta imagen de comida", 
        history: chatHistory,
        image: selectedImageBase64 
    };

    userInput.value = '';
    selectedImageBase64 = null;
    previewContainer.style.display = 'none';
    
    // 2. Mostrar indicador "escribiendo..." de la IA
    chatBox.appendChild(typingMsg);
    typingMsg.style.display = 'flex';
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        // 3. Petición POST a la API de Gemma (Backend en PHP)
        const response = await fetch('../controllers/gamma_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(sendData)
        });

        const data = await response.json();
        
        // Ocultamos el indicador de typing
        typingMsg.style.display = 'none';

        if (data.status === 'success' && data.food_data) {
            const iaResponse = data.food_data;
            
            // Almacenar en la memoria lo que dijo la IA
            chatHistory.push({ role: "model", parts: [{ text: JSON.stringify(iaResponse) }] });
            
            if (iaResponse.tipo_respuesta === 'chat') {
                // Modo Conversación
                appendBotText(iaResponse.mensaje_respuesta);
                playGeminiVoice(iaResponse.mensaje_respuesta);
            } else if (iaResponse.tipo_respuesta === 'food_log') {
                // Modo Registro de Comida o Sugerencia
                renderBotCard(iaResponse);
                // Cuando da la tarjeta, también nos da una descripción o nombre
                const texto = `He registrado ${iaResponse.alimento}. Tiene ${iaResponse.calorias} calorías. ¿Está correcto?`;
                playGeminiVoice(texto);
            }
        } else {
            appendBotText("Lo siento, tuve un problema analizando eso. ¿Puedes repetirlo?");
        }

    } catch (error) {
        typingMsg.style.display = 'none';
        appendBotText("Error de red. Asegúrate de tener conexión.");
    }
}

function appendUserMessage(text, imageB64 = null) {
    let imageHtml = imageB64 ? `<img src="${imageB64}" style="max-width: 100%; border-radius: 8px; margin-bottom: 5px; display: block;">` : '';
    const html = `
    <div class="msg-wrapper user">
        <div class="msg-label">Tú</div>
        <div class="msg-row">
            <div class="bubble user-bubble">
                ${imageHtml}
                ${text}
            </div>
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
        action.innerHTML = `<button class="fc-btn primary" onclick="saveEditData('${templateId}')">Hecho</button>`;
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
        // Desescapar comillas para que el body sí sea JSON válido para PHP
        const validJsonString = jsonDataStr.replace(/&quot;/g, '"');
        
        const response = await fetch('../controllers/guardar_comida_ia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: validJsonString
        });

        const repObj = await response.json();

        if (response.ok && repObj.status === 'success') {
            const actions = document.querySelector(`#${templateId} .fc-actions`);
            actions.innerHTML = '<div style="width: 100%; text-align: center; color: #15803D; font-weight: 700; padding: 0.5rem 0; font-size: 0.9rem;">Guardado correctamente</div>';
        } else {
            btn.innerHTML = 'Fallo al guardar';
            btn.disabled = false;
        }
    } catch (e) {
        btn.innerHTML = 'Error conexión';
        btn.disabled = false;
    }
}

btnSend.addEventListener('click', sendMessage);
userInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') sendMessage();
});

// ==========================================
// MOTOR TTS GEMINI (Texto a Voz nativo)
// ==========================================
async function playGeminiVoice(text) {
    try {
        const res = await fetch('../controllers/gemini_tts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ texto: text })
        });
        
        const data = await res.json();
        if (data.status === 'success' && data.audio_base64) {
            playPCMBase64(data.audio_base64);
        }
    } catch (e) {
        console.error("Error reproduciendo voz:", e);
    }
}

// Función constructora WAV a partir de PCM lineal crudo de Gemini TTS
function playPCMBase64(base64Str) {
    const raw = atob(base64Str);
    const len = raw.length;
    let buffer = new ArrayBuffer(44 + len);
    let view = new DataView(buffer);
    
    // RIFF chunk descriptor
    writeString(view, 0, 'RIFF');
    view.setUint32(4, 36 + len, true);
    writeString(view, 8, 'WAVE');
    
    // FMT sub-chunk
    writeString(view, 12, 'fmt ');
    view.setUint32(16, 16, true);
    view.setUint16(20, 1, true); // PCM format = 1
    view.setUint16(22, 1, true); // Mono channel = 1
    view.setUint32(24, 24000, true); // Sample rate = 24000Hz (Default Gemini)
    view.setUint32(28, 24000 * 2, true); // Byte rate
    view.setUint16(32, 2, true); // Block align
    view.setUint16(34, 16, true); // Bits per sample = 16
    
    // Data sub-chunk
    writeString(view, 36, 'data');
    view.setUint32(40, len, true);
    
    // Escribir la data PCM exacta
    let offset = 44;
    for (let i = 0; i < len; i++) {
        view.setUint8(offset + i, raw.charCodeAt(i));
    }
    
    const blob = new Blob([buffer], { type: 'audio/wav' });
    const url = URL.createObjectURL(blob);
    const audio = new Audio(url);
    audio.play();
}

function writeString(view, offset, string) {
    for (let i = 0; i < string.length; i++) {
        view.setUint8(offset + i, string.charCodeAt(i));
    }
}
