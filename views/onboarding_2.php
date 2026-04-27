<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.html");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['objetivo'])) {
    $_SESSION['onboarding_objetivo'] = $_POST['objetivo'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NutrIAssist - Sobre ti</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css">
    <style>
        .mobile-container { background-color: #FAFBFA; }

        /* Bottom sheet / selector overlay */
        .sheet-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 100;
            align-items: flex-end;
        }
        .sheet-overlay.open { display: flex; }

        .sheet {
            background: white;
            width: 100%;
            max-width: 430px;
            margin: 0 auto;
            border-radius: 20px 20px 0 0;
            padding: 1.25rem 1.5rem 2rem;
        }

        .sheet-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--color-text-dark);
        }

        .sheet select,
        .sheet input[type="number"] {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1.5px solid var(--color-border);
            border-radius: 14px;
            font-size: 1rem;
            color: var(--color-text-dark);
            outline: none;
            margin-bottom: 1rem;
            appearance: none;
            background: white;
        }
        .sheet select:focus,
        .sheet input[type="number"]:focus {
            border-color: var(--color-malachite);
            box-shadow: 0 0 0 3px var(--color-mint);
        }

        .sheet-confirm {
            width: 100%;
            padding: 0.875rem;
            background: var(--color-primary);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="mobile-container">

        <div class="nav-bar">
            <a href="onboarding_1.php" class="back-btn" aria-label="Volver"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg></a>
            <div class="progress-segments">
                <div class="progress-seg active"></div>
                <div class="progress-seg active"></div>
                <div class="progress-seg"></div>
            </div>
        </div>

        <!-- Ícono héroe: persona/usuario -->
        <div class="icon-circle">
            <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="26" cy="18" r="9" stroke="#11CF50" stroke-width="3"/>
                <path d="M8 44c0-9.941 8.059-18 18-18s18 8.059 18 18" stroke="#11CF50" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>

        <div class="screen-header">
            <h1>Sobre ti</h1>
            <p>Esta información nos ayudará a calcular tus calorías objetivo</p>
        </div>

        <!-- Formulario oculto con los verdaderos valores -->
        <form id="form-sobre-ti" action="onboarding_3.php" method="POST" class="flex-form-container">
            <input type="hidden" id="h-sexo"   name="sexo"   value="">
            <input type="hidden" id="h-edad"   name="edad"   value="">
            <input type="hidden" id="h-altura" name="altura" value="">
            <input type="hidden" id="h-peso"   name="peso"   value="">

            <!-- Filas de información visible -->
            <div class="info-rows">

                <div class="info-row" onclick="openSheet('sexo')">
                    <div class="info-row-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M12 12v8M8 20h8"/>
                        </svg>
                    </div>
                    <span class="info-row-label">Sexo</span>
                    <span class="info-row-value">
                        <span id="val-sexo">Hombre</span>
                        <span class="chevron">›</span>
                    </span>
                </div>

                <div class="info-row" onclick="openSheet('edad')">
                    <div class="info-row-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8"  y1="2" x2="8"  y2="6"/>
                            <line x1="3"  y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <span class="info-row-label">Edad</span>
                    <span class="info-row-value">
                        <span id="val-edad">24</span>
                        <span class="chevron">›</span>
                    </span>
                </div>

                <div class="info-row" onclick="openSheet('altura')">
                    <div class="info-row-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 21V3M8 3l-3 3M8 3l3 3"/>
                            <line x1="13" y1="6"  x2="19" y2="6"/>
                            <line x1="13" y1="10" x2="19" y2="10"/>
                            <line x1="13" y1="14" x2="19" y2="14"/>
                            <line x1="13" y1="18" x2="19" y2="18"/>
                        </svg>
                    </div>
                    <span class="info-row-label">Altura</span>
                    <span class="info-row-value">
                        <span id="val-altura">178 cm</span>
                        <span class="chevron">›</span>
                    </span>
                </div>

                <div class="info-row" onclick="openSheet('peso')">
                    <div class="info-row-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3"  y1="12" x2="21" y2="12"/>
                            <line x1="3"  y1="6"  x2="21" y2="6"/>
                            <line x1="3"  y1="18" x2="21" y2="18"/>
                        </svg>
                    </div>
                    <span class="info-row-label">Peso</span>
                    <span class="info-row-value">
                        <span id="val-peso">75 kg</span>
                        <span class="chevron">›</span>
                    </span>
                </div>
            </div>

            <div class="sticky-footer">
                <button type="button" class="btn-primary" onclick="submitForm()">Continuar</button>
            </div>
        </form>
    </div>

    <!-- Bottom Sheets -->

    <!-- Sexo -->
    <div class="sheet-overlay" id="sheet-sexo" onclick="closeOnOverlay(event,'sheet-sexo')">
        <div class="sheet">
            <div class="sheet-title">Selecciona tu sexo</div>
            <select id="sel-sexo">
                <option value="M">Masculino (Hombre)</option>
                <option value="F">Femenino (Mujer)</option>
            </select>
            <button class="sheet-confirm" onclick="confirmSexo()">Confirmar</button>
        </div>
    </div>

    <!-- Edad -->
    <div class="sheet-overlay" id="sheet-edad" onclick="closeOnOverlay(event,'sheet-edad')">
        <div class="sheet">
            <div class="sheet-title">Ingresa tu edad</div>
            <input type="number" id="inp-edad" min="15" max="100" value="24" placeholder="Ej. 24">
            <button class="sheet-confirm" onclick="confirmEdad()">Confirmar</button>
        </div>
    </div>

    <!-- Altura -->
    <div class="sheet-overlay" id="sheet-altura" onclick="closeOnOverlay(event,'sheet-altura')">
        <div class="sheet">
            <div class="sheet-title">Ingresa tu altura (cm)</div>
            <input type="number" id="inp-altura" min="100" max="250" value="178" placeholder="Ej. 178">
            <button class="sheet-confirm" onclick="confirmAltura()">Confirmar</button>
        </div>
    </div>

    <!-- Peso -->
    <div class="sheet-overlay" id="sheet-peso" onclick="closeOnOverlay(event,'sheet-peso')">
        <div class="sheet">
            <div class="sheet-title">Ingresa tu peso (kg)</div>
            <input type="number" id="inp-peso" min="30" max="300" step="0.1" value="75" placeholder="Ej. 75">
            <button class="sheet-confirm" onclick="confirmPeso()">Confirmar</button>
        </div>
    </div>

    <script>
        // Estado interno
        const state = { sexo: 'M', edad: 24, altura: 178, peso: 75 };

        // Inicializar valores en los campos ocultos
        window.onload = () => {
            document.getElementById('h-sexo').value   = state.sexo;
            document.getElementById('h-edad').value   = state.edad;
            document.getElementById('h-altura').value = state.altura;
            document.getElementById('h-peso').value   = state.peso;
        };

        function openSheet(name) {
            document.getElementById('sheet-' + name).classList.add('open');
        }
        function closeSheet(name) {
            document.getElementById('sheet-' + name).classList.remove('open');
        }
        function closeOnOverlay(e, name) {
            if (e.target === e.currentTarget) closeSheet(name);
        }

        function confirmSexo() {
            const v = document.getElementById('sel-sexo').value;
            state.sexo = v;
            document.getElementById('val-sexo').textContent = v === 'M' ? 'Hombre' : 'Mujer';
            document.getElementById('h-sexo').value = v;
            closeSheet('sexo');
        }
        function confirmEdad() {
            const v = parseInt(document.getElementById('inp-edad').value);
            if (v >= 15 && v <= 100) {
                state.edad = v;
                document.getElementById('val-edad').textContent = v;
                document.getElementById('h-edad').value = v;
            }
            closeSheet('edad');
        }
        function confirmAltura() {
            const v = parseInt(document.getElementById('inp-altura').value);
            if (v >= 100 && v <= 250) {
                state.altura = v;
                document.getElementById('val-altura').textContent = v + ' cm';
                document.getElementById('h-altura').value = v;
            }
            closeSheet('altura');
        }
        function confirmPeso() {
            const v = parseFloat(document.getElementById('inp-peso').value);
            if (v >= 30 && v <= 300) {
                state.peso = v;
                document.getElementById('val-peso').textContent = v + ' kg';
                document.getElementById('h-peso').value = v;
            }
            closeSheet('peso');
        }

        function submitForm() {
            // Asegurarnos de que todos los valores estén seteados
            if (!document.getElementById('h-sexo').value ||
                !document.getElementById('h-edad').value ||
                !document.getElementById('h-altura').value ||
                !document.getElementById('h-peso').value) {
                alert('Por favor completa todos los campos.');
                return;
            }
            document.getElementById('form-sobre-ti').submit();
        }
    </script>
</body>
</html>