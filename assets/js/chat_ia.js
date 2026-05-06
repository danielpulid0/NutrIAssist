const chatBox = document.getElementById('chat-box');
const userInput = document.getElementById('user-input');
const btnSend = document.getElementById('btn-send');
const typingMsg = document.getElementById('typing-msg');

let chatHistory = [];
let selectedImageBase64 = null;
let selectedAudioBase64 = null;
let selectedAudioMimeType = 'audio/webm'; 
let mediaRecorder;
let audioChunks = [];
let recordingInterval;
let startTime;
let isMuted = false; // Estado del habla de la IA

// Elementos de la UI
const btnCamera = document.getElementById('btn-camera');
const btnGallery = document.getElementById('btn-gallery');
const inputCamera = document.getElementById('input-camera');
const inputGallery = document.getElementById('input-gallery');
const previewContainer = document.getElementById('image-preview-container');
const previewImg = document.getElementById('image-preview');
const btnRemoveImg = document.getElementById('remove-image');

// Elementos de audio UI
const btnMic = document.getElementById('btn-mic');
const audioPreview = document.getElementById('audio-preview-container');
const recordingStatus = document.getElementById('recording-status');
const pulse = document.querySelector('.recording-pulse');
const btnStopRec = document.getElementById('stop-recording');
const btnRemoveAudio = document.getElementById('remove-audio');

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
        previewContainer.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}

// Quitar imagen
btnRemoveImg.addEventListener('click', () => {
    selectedImageBase64 = null;
    previewContainer.classList.add('hidden');
    inputCamera.value = '';
    inputGallery.value = '';
});

// EVENTOS DE AUDIO
btnMic.addEventListener('click', startRecording);
btnStopRec.addEventListener('click', stopRecording);
btnRemoveAudio.addEventListener('click', () => {
    selectedAudioBase64 = null;
    audioPreview.classList.add('hidden');
});

// EVENTO MUTE TTS
const btnMute = document.getElementById('btn-mute');
const volWaves = document.getElementById('vol-waves');
btnMute.addEventListener('click', () => {
    isMuted = !isMuted;
    if (isMuted) {
        volWaves.style.display = 'none';
        btnMute.style.opacity = '0.5';
        btnMute.title = "Activar sonido";
    } else {
        volWaves.style.display = 'block';
        btnMute.style.opacity = '1';
        btnMute.title = "Silenciar asistente";
    }
});

async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

        // Detectar el MIME type real que soporta el navegador
        const mimeType = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm'
                        : MediaRecorder.isTypeSupported('audio/ogg') ? 'audio/ogg'
                        : 'audio/mp4';

        mediaRecorder = new MediaRecorder(stream, { mimeType });
        audioChunks = [];

        mediaRecorder.ondataavailable = (e) => audioChunks.push(e.data);
        mediaRecorder.onstop = async () => {
            const audioBlob = new Blob(audioChunks, { type: mimeType });
            selectedAudioBase64 = await blobToBase64(audioBlob);
            selectedAudioMimeType = mimeType;
            recordingStatus.innerText = "Audio de voz capturado ✓";
            pulse.classList.remove('active');
            btnStopRec.classList.add('hidden');
        };

        mediaRecorder.start();
        startTime = Date.now();
        audioPreview.classList.remove('hidden');
        audioPreview.classList.add('active');
        pulse.classList.remove('hidden');
        pulse.classList.add('active');
        btnStopRec.classList.remove('hidden');
        
        recordingInterval = setInterval(() => {
            const seconds = Math.floor((Date.now() - startTime) / 1000);
            recordingStatus.innerText = `Grabando Audio... 0:${seconds < 10 ? '0' : ''}${seconds}`;
        }, 1000);
    } catch (err) {
        console.error("No se pudo acceder al micrófono:", err);
    }
}

function stopRecording() {
    if(mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }
    clearInterval(recordingInterval);
}

function blobToBase64(blob) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.readAsDataURL(blob);
    });
}

// ==========================================
// DRAG AND DROP SOPORTE
// ==========================================
const dropZone = document.querySelector('.mobile-container');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragging');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragging');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragging');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        handleImageSelect(file);
    }
});

async function sendMessage() {
    let text = userInput.value.trim();
    const currentAudio = selectedAudioBase64;
    const currentImage = selectedImageBase64;

    if (!text && !currentImage && !currentAudio) return;

    // 1. Limpieza inmediata de la UI para el siguiente mensaje
    userInput.value = '';
    selectedImageBase64 = null;
    selectedAudioBase64 = null;
    previewContainer.classList.add('hidden');
    audioPreview.classList.add('hidden');
    audioPreview.classList.remove('active');

    // 2. CASO ESPECIAL: ES UN AUDIO SIN TEXTO
    if (!text && currentAudio) {
        // Mostramos la burbuja de audio de inmediato para que el usuario sienta rapidez
        appendUserMessage("Transcribiendo audio...", null, currentAudio);

        try {
            // DETENER EJECUCIÓN: Esperar a que el backend de Google nos dé el texto real
            const transResponse = await fetch('../api/transcribe_audio.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ audio: currentAudio, mimeType: selectedAudioMimeType })
            });
            const transData = await transResponse.json();
            
            // EL TEXTO PASA DE "Transcribiendo..." A LO QUE REALMENTE DIJERON
            text = transData.transcripcion || "Audio enviado";

            // Actualizar visualmente la burbuja del usuario para que vea su texto transcrito
            const lastBubble = chatBox.querySelector('.msg-wrapper.user:last-of-type .bubble');
            if(lastBubble) {
                lastBubble.innerHTML = lastBubble.innerHTML.replace("Transcribiendo audio...", `<div style="font-size: 0.75rem; opacity: 0.7; margin-top: 4px; font-style: italic; max-width: 200px; line-height: 1.2;">"${text}"</div>`);
            }
        } catch (e) {
            text = "Audio enviado";
        }
    } else {
        // Envió texto o imagen normal
        appendUserMessage(text, currentImage, currentAudio);
    }

    // 3. SOLO AHORA QUE TENEMOS EL TEXTO FINAL (sea de input o audio), MANDAMOS A LA IA
    chatHistory.push({ role: "user", parts: [{ text: text }] });

    try {
        const sendData = { 
            prompt: text, // Es lo que transcribimos arriba si fue un audio
            history: chatHistory,
            image: currentImage,
            audio: null 
        };
        
        // Mostrar indicador de "IA escribiendo..."
        chatBox.appendChild(typingMsg);
        typingMsg.classList.remove('hidden');
        typingMsg.style.display = 'flex';
        chatBox.scroll({ top: chatBox.scrollHeight, behavior: 'smooth' });

        const response = await fetch('../api/gamma_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(sendData)
        });

        const data = await response.json();
        typingMsg.classList.add('hidden');
        typingMsg.style.display = 'none';

        if (data.status === 'success' && data.food_data) {
            const iaResponse = data.food_data;
            chatHistory.push({ role: "model", parts: [{ text: JSON.stringify(iaResponse) }] });
            
            if (iaResponse.tipo_respuesta === 'chat') {
                appendBotText(iaResponse.mensaje_respuesta);
                playGeminiVoice(iaResponse.mensaje_respuesta);
            } else if (iaResponse.tipo_respuesta === 'food_log') {
                renderBotCard(iaResponse);
                const desc = `He registrado ${iaResponse.alimento}. Tiene ${iaResponse.calorias} calorías. ¿Está correcto?`;
                playGeminiVoice(desc);
            }
        } else {
            appendBotText("Lo siento, tuve un problema analizando eso. ¿Puedes repetirlo?");
        }
    } catch (error) {
        typingMsg.classList.add('hidden');
        typingMsg.style.display = 'none';
        appendBotText("Error de red. Asegúrate de tener conexión.");
    }
}

function appendUserMessage(text, imageB64 = null, audioB64 = null) {
    let imageHtml = imageB64 ? `<img src="${imageB64}" style="max-width: 100%; border-radius: 8px; margin-bottom: 5px; display: block;">` : '';
    let audioHtml = "";
    if (audioB64) {
        audioHtml = `
            <audio controls src="${audioB64}" style="width: 200px; height: 36px; display: block; border-radius: 20px;"></audio>
            <div style="font-size: 0.75rem; opacity: 0.7; margin-top: 4px; font-style: italic; max-width: 200px; line-height: 1.2;">
                "${text}"
            </div>
        `;
    }
    
    const html = `
    <div class="msg-wrapper user">
        <div class="msg-label">Tú</div>
        <div class="msg-row">
            <div class="bubble user-bubble">
                ${imageHtml}
                ${audioHtml}
                ${!audioB64 ? text : ''} 
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

// Helper: coerce any value to a number (strips units like 'kcal', 'g', etc.)
function toNum(val) {
    if (typeof val === 'number') return val;
    const cleaned = String(val).replace(/[^0-9.]/g, '');
    const num = parseFloat(cleaned);
    return isNaN(num) ? 0 : num;
}

// Helper: escape strings for safe use in HTML attributes
function escAttr(str) {
    return String(str).replace(/&/g,'&amp;').replace(/'/g,'&#39;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function renderBotCard(data) {
    const templateId = 'card_' + Date.now();
    const calorias = Math.round(toNum(data.calorias));
    const alimento = (data.alimento || "Alimento Detectado").replace(/['"\\/]/g, '');
    const detalle = (data.descripcion || "Porción estimada").replace(/['"\\/]/g, '');
    const comida = data.tipo_comida || "Registro";
    const proteina = toNum(data.proteina);
    const carbs = toNum(data.carbs);
    const grasas = toNum(data.grasas);

    const isLiquid = data.tipo_icono === 'liquid' || alimento.toLowerCase().includes('jugo') || alimento.toLowerCase().includes('leche') || alimento.toLowerCase().includes('café');
    const iconClass = isLiquid ? 'liquid' : 'solid';
    const iconSvg = isLiquid 
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 2l4 4-4 4"></path><path d="M12 2v20"></path><path d="M20 20a4 4 0 0 1-8 0c0-2.2 4-7 4-7s4 4.8 4 7z"></path></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>';

    // Imágenes aleatorias de comida si no hay una específica (opcional, por ahora una genérica bonita)
    const foodImgs = [
        'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=140&fit=crop',
        'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=400&h=140&fit=crop',
        'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&h=140&fit=crop'
    ];
    const bannerImg = foodImgs[Math.floor(Math.random() * foodImgs.length)];

    // Store payload as data attribute to avoid inline onclick string-escaping issues
    const payloadObj = { calorias, proteina, carbs, grasas, alimento, descripcion: detalle, tipo_comida: comida };
    const safePayload = escAttr(JSON.stringify(payloadObj));
    const safeAlimento = escAttr(alimento);
    const safeComida = escAttr(comida);

    const html = `
    <div class="msg-wrapper ai" id="${templateId}">
        <div class="msg-label">NutrIAssist</div>
        <div class="msg-row">
            <div class="avatar bot-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><circle cx="12" cy="5" r="2"></circle><path d="M12 7v4"></path></svg>
            </div>
            <div class="food-card" data-payload="${safePayload}">
                <div class="fc-banner" style="background-image: url('${bannerImg}');">
                    <div class="fc-banner-overlay">
                        <h3>${comida}</h3>
                        <p>${alimento}</p>
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
                    <button class="fc-btn outline" onclick="editFoodData('${templateId}', ${calorias}, ${proteina}, ${carbs}, ${grasas}, '${safeAlimento}', '${safeComida}')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        Editar
                    </button>
                    <button class="fc-btn primary confirm-btn" onclick="saveFoodData('${templateId}')">
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


function editFoodData(templateId, c, p, cb, g, alimento, comida) {
    const card = document.getElementById(templateId);
    if(card) {
        const body = card.querySelector('.fc-body');
        body.innerHTML = `
            <div class="fc-edit-pane">
                <h4 class="fc-edit-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Ajustar Macros
                </h4>
                <div class="fc-macro-grid">
                    <div class="fc-macro-col span-2">
                        <label class="fc-macro-label">EN QUÉ COMIDA REGISTRAR</label>
                        <select id="e_tipo_${templateId}" class="fc-macro-input">
                            <option value="Desayuno" ${comida.toLowerCase()=='desayuno'?'selected':''}>☀️ Desayuno</option>
                            <option value="Comida" ${comida.toLowerCase()=='comida'?'selected':''}>🌱 Comida</option>
                            <option value="Cena" ${comida.toLowerCase()=='cena'?'selected':''}>🌙 Cena</option>
                            <option value="Snack" ${!['desayuno','comida','cena'].includes(comida.toLowerCase())?'selected':''}>🍪 Snack</option>
                        </select>
                    </div>
                    <div class="fc-macro-col">
                        <label class="fc-macro-label">CALORÍAS</label> 
                        <input type="number" id="e_cal_${templateId}" value="${c}" class="fc-macro-input">
                    </div>
                    <div class="fc-macro-col">
                        <label class="fc-macro-label">PROTEÍNA (g)</label> 
                        <input type="number" id="e_pro_${templateId}" value="${p}" class="fc-macro-input">
                    </div>
                    <div class="fc-macro-col">
                        <label class="fc-macro-label">CARBS (g)</label> 
                        <input type="number" id="e_car_${templateId}" value="${cb}" class="fc-macro-input">
                    </div>
                    <div class="fc-macro-col">
                        <label class="fc-macro-label">GRASAS (g)</label> 
                        <input type="number" id="e_fat_${templateId}" value="${g}" class="fc-macro-input">
                    </div>
                </div>
            </div>
        `;
        const action = card.querySelector('.fc-actions');
        action.innerHTML = `<button class="fc-btn primary fc-btn-full" onclick="saveEditData('${templateId}', '${alimento}', '${comida}')">Actualizar Valores</button>`;
    }
}

function saveEditData(templateId, alimento, comida) {
    const c = toNum(document.getElementById('e_cal_'+templateId).value);
    const p = toNum(document.getElementById('e_pro_'+templateId).value);
    const cb = toNum(document.getElementById('e_car_'+templateId).value);
    const f = toNum(document.getElementById('e_fat_'+templateId).value);
    const t = document.getElementById('e_tipo_'+templateId) ? document.getElementById('e_tipo_'+templateId).value : comida;
    
    // Update the data-payload on the card for the confirm button
    const updatedPayload = { calorias: c, proteina: p, carbs: cb, grasas: f, alimento: alimento, tipo_comida: t };
    const card = document.getElementById(templateId);
    const foodCard = card.querySelector('.food-card');
    if (foodCard) foodCard.setAttribute('data-payload', JSON.stringify(updatedPayload));
    
    const bannerSubtitle = document.querySelector(`#${templateId} .fc-banner-overlay h3`);
    if(bannerSubtitle) bannerSubtitle.innerText = t;
    
    // Actualizar también el badge de calorías en el banner
    const badge = document.querySelector(`#${templateId} .fc-badge`);
    if(badge) badge.innerText = `+${c} kcal`;

    const body = document.querySelector(`#${templateId} .fc-body`);
    body.innerHTML = `
        <div class="fc-success-pane">
            <div class="fc-success-badge-container">
                <div class="fc-success-icon">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="4"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <span class="fc-success-text">Valores ajustados</span>
            </div>
            <div class="fc-adjusted-values">
                <div class="fc-adjusted-item"><b>${p}g</b> Prot</div>
                <div class="fc-adjusted-item"><b>${cb}g</b> Carb</div>
                <div class="fc-adjusted-item"><b>${f}g</b> Fat</div>
            </div>
        </div>
    `;
    
    const safeAlimento = escAttr(alimento);
    const safeComida = escAttr(t);
    const action = document.querySelector(`#${templateId} .fc-actions`);
    action.innerHTML = `
        <button class="fc-btn outline" onclick="editFoodData('${templateId}', ${c}, ${p}, ${cb}, ${f}, '${safeAlimento}', '${safeComida}')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
            Editar
        </button>
        <button class="fc-btn primary confirm-btn" onclick="saveFoodData('${templateId}')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Confirmar
        </button>
    `;
}



async function saveFoodData(templateId) {
    const btn = document.querySelector(`#${templateId} .confirm-btn`);
    btn.innerHTML = 'Guardando...';
    btn.style.backgroundColor = '#CBD5E1';
    btn.style.color = '#334155';
    btn.disabled = true;

    try {
        // Read payload from data attribute (safe from HTML escaping issues)
        const foodCard = document.querySelector(`#${templateId} .food-card`);
        const rawPayload = foodCard.getAttribute('data-payload');
        const payloadObj = JSON.parse(rawPayload);
        
        // Final numeric safety net
        payloadObj.calorias = toNum(payloadObj.calorias);
        payloadObj.proteina = toNum(payloadObj.proteina);
        payloadObj.carbs    = toNum(payloadObj.carbs);
        payloadObj.grasas   = toNum(payloadObj.grasas);
        
        console.log('[NutrIAssist] Saving food:', payloadObj);
        
        const response = await fetch('../api/guardar_comida_ia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payloadObj)
        });

        const repObj = await response.json();
        console.log('[NutrIAssist] Save response:', repObj);

        if (response.ok && repObj.status === 'success') {
            const actions = document.querySelector(`#${templateId} .fc-actions`);
            actions.innerHTML = '<div class="fc-msg-full">✅ Guardado correctamente</div>';
        } else {
            console.error('[NutrIAssist] Save failed:', repObj);
            btn.innerHTML = 'Fallo al guardar';
            btn.disabled = false;
        }
    } catch (e) {
        console.error('[NutrIAssist] Save error:', e);
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
    if (isMuted) return; // NO gastar tokens si está silenciado
    try {
        const res = await fetch('../api/gemini_tts.php', {
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
