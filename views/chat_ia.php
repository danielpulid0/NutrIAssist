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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    
    <style>
        /* Ajuste del contenedor para el chat */
        .mobile-container {
            padding: 0; /* Quitamos el padding para aprovechar toda la pantalla */
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: var(--color-bg-app); /* Fondo gris claro para contrastar globos */
        }

        .chat-header {
            background-color: var(--color-bg);
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            border-bottom: 1px solid var(--color-border);
            text-align: center;
            z-index: 10;
        }

        .chat-header h1 { font-size: 1.25rem; margin-bottom: 0.2rem; }
        .chat-header p { font-size: 0.85rem; color: var(--color-primary); font-weight: 600; }

        /* Área de mensajes */
        .chat-messages {
            flex-grow: 1;
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-bottom: 120px; /* Espacio para el input y el footer */
        }

        /* Globos de Chat */
        .message {
            max-width: 85%;
            padding: 0.8rem 1rem;
            border-radius: 16px;
            font-size: 0.95rem;
            line-height: 1.4;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            background-color: var(--color-primary);
            color: var(--color-text-dark);
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .message.ai {
            background-color: var(--color-bg);
            color: var(--color-text-dark);
            align-self: flex-start;
            border: 1px solid var(--color-border);
            border-bottom-left-radius: 4px;
        }

        /* Plantilla Editable (Human-in-the-loop) */
        .editable-template {
            background-color: var(--color-bg);
            border: 2px solid var(--color-malachite);
            border-radius: 16px;
            padding: 1rem;
            width: 100%;
            margin-top: 0.5rem;
            box-shadow: 0 4px 12px rgba(17, 207, 80, 0.15);
        }

        .editable-template h3 { font-size: 1rem; margin-bottom: 1rem; color: var(--color-text-dark); text-align: center;}
        
        .macro-edit-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .macro-edit-group label { font-size: 0.75rem; color: var(--color-text-gray); }
        .macro-edit-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 0.2rem;
        }

        /* Área de Input Fija */
        .chat-input-area {
            position: absolute;
            bottom: 70px; /* Justo arriba de tu footer.php */
            left: 0;
            width: 100%;
            background-color: var(--color-bg);
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--color-border);
            display: flex;
            gap: 0.5rem;
            z-index: 100;
        }

        .chat-input-area input {
            flex-grow: 1;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-border);
            border-radius: 24px;
            font-size: 0.95rem;
            outline: none;
        }

        .chat-input-area input:focus { border-color: var(--color-malachite); }

        .btn-send {
            background-color: var(--color-primary);
            color: var(--color-text-dark);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.2rem;
            cursor: pointer;
        }

        /* Animación de "Gemma escribiendo..." */
        .typing-indicator { display: none; align-self: flex-start; background: transparent; border: none; padding: 0.5rem; }
        .typing-indicator span { display: inline-block; width: 8px; height: 8px; background-color: var(--color-text-gray); border-radius: 50%; margin: 0 2px; animation: bounce 1.4s infinite ease-in-out both; }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
    </style>
</head>
<body>

    <div class="mobile-container">
        
        <div class="chat-header">
            <h1>Asistente Gemma</h1>
            <p>🟢 En línea</p>
        </div>

        <div class="chat-messages" id="chat-box">
            <div class="message ai">
                ¡Hola! Soy Gemma. Dime, ¿qué comiste hoy o qué planeas comer? (Ej. "Desayuné 2 huevos revueltos con 2 tortillas").
            </div>
            
            <div class="typing-indicator" id="typing">
                <span></span><span></span><span></span>
            </div>
        </div>

        <div class="chat-input-area">
            <input type="text" id="user-input" placeholder="Escribe tu comida aquí..." autocomplete="off">
            <button class="btn-send" id="btn-send">🔼</button>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const userInput = document.getElementById('user-input');
        const btnSend = document.getElementById('btn-send');
        const typingIndicator = document.getElementById('typing');

        // Función para enviar el mensaje
        async function sendMessage() {
            const text = userInput.value.trim();
            if (!text) return;

            // 1. Mostrar el mensaje del usuario en la UI
            appendMessage(text, 'user');
            userInput.value = '';
            
            // 2. Mostrar "Gemma escribiendo..."
            chatBox.appendChild(typingIndicator); // Mueve el indicador al final
            typingIndicator.style.display = 'block';
            chatBox.scrollTop = chatBox.scrollHeight; // Auto-scroll hacia abajo

            try {
                // 3. Petición real al backend PHP (que conectará con Gemma)
                const response = await fetch('../controllers/api_gemma.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: text })
                });

                const data = await response.json();
                
                // Ocultar indicador
                typingIndicator.style.display = 'none';

                // 4. Si la IA entendió la comida, mostrar la plantilla editable
                if(data.status === 'success') {
                    appendMessage("He analizado tu comida. Por favor, confirma o edita los valores antes de guardar:", 'ai');
                    renderEditableTemplate(data.food_data);
                } else {
                    appendMessage("No pude entender bien eso. ¿Podrías ser más específico con las porciones?", 'ai');
                }

            } catch (error) {
                typingIndicator.style.display = 'none';
                // MOCK DE PRUEBA: Si el backend aún no existe o falla, mostramos una plantilla de prueba para que veas la UI
                appendMessage("He analizado tu comida. Por favor, confirma o edita los valores antes de guardar:", 'ai');
                renderEditableTemplate({
                    alimento: "Huevos Revueltos con Tortilla",
                    cantidad: "200",
                    unidad: "gramos",
                    calorias: 320,
                    proteina: 14,
                    carbs: 25,
                    grasas: 18,
                    tipo_comida: "Desayuno"
                });
            }
        }

        // Función auxiliar para pintar globos de chat
        function appendMessage(text, sender) {
            const div = document.createElement('div');
            div.classList.add('message', sender);
            div.textContent = text;
            chatBox.insertBefore(div, typingIndicator); // Inserta antes del indicador de carga
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Función CRÍTICA: Renderizar la plantilla que el usuario puede editar (Human-in-the-loop)
        function renderEditableTemplate(foodData) {
            const templateId = 'form_' + Date.now(); // ID único por si registra varias cosas
            
            const html = `
                <div class="editable-template" id="${templateId}">
                    <h3>📝 Confirmar Registro</h3>
                    <div class="macro-edit-group" style="margin-bottom: 0.5rem;">
                        <label>Alimento detectado</label>
                        <input type="text" id="${templateId}_nombre" value="${foodData.alimento}">
                    </div>
                    
                    <div class="macro-edit-grid">
                        <div class="macro-edit-group">
                            <label>Comida</label>
                            <select id="${templateId}_tipo" style="width:100%; padding:0.5rem; border-radius:8px; border:1px solid #E5E7EB;">
                                <option value="Desayuno" ${foodData.tipo_comida === 'Desayuno' ? 'selected' : ''}>Desayuno</option>
                                <option value="Comida" ${foodData.tipo_comida === 'Comida' ? 'selected' : ''}>Comida</option>
                                <option value="Cena" ${foodData.tipo_comida === 'Cena' ? 'selected' : ''}>Cena</option>
                                <option value="Snack" ${foodData.tipo_comida === 'Snack' ? 'selected' : ''}>Snack</option>
                            </select>
                        </div>
                        <div class="macro-edit-group">
                            <label>Calorías (kcal)</label>
                            <input type="number" id="${templateId}_cal" value="${foodData.calorias}">
                        </div>
                        <div class="macro-edit-group">
                            <label>Proteína (g)</label>
                            <input type="number" id="${templateId}_pro" value="${foodData.proteina}">
                        </div>
                        <div class="macro-edit-group">
                            <label>Carbohidratos (g)</label>
                            <input type="number" id="${templateId}_car" value="${foodData.carbs}">
                        </div>
                    </div>
                    
                    <button class="btn-primary" onclick="saveFoodData('${templateId}')">Confirmar y Guardar</button>
                </div>
            `;
            
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            chatBox.insertBefore(wrapper.firstElementChild, typingIndicator);
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Función que atrapa los datos editados y los envía al backend final
        async function saveFoodData(templateId) {
            const finalData = {
                nombre: document.getElementById(templateId + '_nombre').value,
                tipo: document.getElementById(templateId + '_tipo').value,
                calorias: document.getElementById(templateId + '_cal').value,
                proteina: document.getElementById(templateId + '_pro').value,
                carbs: document.getElementById(templateId + '_car').value
            };

            // Cambiar el botón a estado de carga
            const btn = document.querySelector(`#${templateId} button`);
            btn.textContent = 'Guardando...';
            btn.style.backgroundColor = 'var(--color-text-gray)';

            // Aquí haremos el fetch final a guardar_comida.php
            // await fetch('../controllers/guardar_comida.php', { ... })
            
            setTimeout(() => {
                // Simulamos éxito
                document.getElementById(templateId).innerHTML = `<div style="text-align:center; color: var(--color-malachite); font-weight:600;">✅ ¡Registro guardado exitosamente en tu diario!</div>`;
            }, 1000);
        }

        // Listeners para el enter y el botón
        btnSend.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') sendMessage();
        });
    </script>
</body>
</html>