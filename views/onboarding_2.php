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
    <link rel="stylesheet" href="../assets/css/onboarding.css">
</head>
<body>

    <div class="mobile-container onboarding-container">

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
            <input type="hidden" id="h-fecha"  name="fecha_nacimiento" value="">
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

                <div class="info-row" onclick="openSheet('fecha_nacimiento')">
                    <div class="info-row-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#6B7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8"  y1="2" x2="8"  y2="6"/>
                            <line x1="3"  y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <span class="info-row-label">Fecha de Nacimiento</span>
                    <span class="info-row-value">
                        <span id="val-fecha">1995-01-01</span>
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
            <div class="select-wrapper">
                <select id="sel-sexo">
                    <option value="M">Masculino (Hombre)</option>
                    <option value="F">Femenino (Mujer)</option>
                </select>
            </div>
            <button class="sheet-confirm" onclick="confirmSexo()">Confirmar</button>
        </div>
    </div>

    <!-- Fecha de Nacimiento -->
    <div class="sheet-overlay" id="sheet-fecha_nacimiento" onclick="closeOnOverlay(event,'sheet-fecha_nacimiento')">
        <div class="sheet">
            <div class="sheet-title">Selecciona tu fecha de nacimiento</div>
            
            <div class="date-wheel-container">
                <div class="picker-highlight"></div>
                
                <!-- Día -->
                <div class="wheel-col" id="wheel-day">
                    <div class="wheel-list"></div>
                </div>
                
                <!-- Mes -->
                <div class="wheel-col" id="wheel-month">
                    <div class="wheel-list"></div>
                </div>
                
                <!-- Año -->
                <div class="wheel-col" id="wheel-year">
                    <div class="wheel-list"></div>
                </div>
            </div>

            <button class="sheet-confirm" onclick="confirmFecha()">Confirmar</button>
        </div>
    </div>

    <!-- Altura -->
    <div class="sheet-overlay" id="sheet-altura" onclick="closeOnOverlay(event,'sheet-altura')">
        <div class="sheet">
            <div class="sheet-title">Ingresa tu altura (cm)</div>
            <input type="number" id="inp-altura" min="50" max="245" value="178" placeholder="Ej. 178" 
                onkeypress="if(event.charCode >= 48 && event.charCode <= 57) { const v = parseInt(this.value + String.fromCharCode(event.charCode)); if(v > 245) return false; }"
                oninput="if(this.value > 245) this.value = 245; if(this.value < 0) this.value = 0;">
            <button class="sheet-confirm" onclick="confirmAltura()">Confirmar</button>
        </div>
    </div>

    <!-- Peso -->
    <div class="sheet-overlay" id="sheet-peso" onclick="closeOnOverlay(event,'sheet-peso')">
        <div class="sheet">
            <div class="sheet-title">Ingresa tu peso (kg)</div>
            <input type="number" id="inp-peso" min="15" max="635" step="0.1" value="75" placeholder="Ej. 75.5" 
                onkeypress="if((event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46) { const v = parseFloat(this.value + String.fromCharCode(event.charCode)); if(v > 635) return false; }"
                oninput="if(this.value > 635) this.value = 635; if(this.value < 0) this.value = 0;">
            <button class="sheet-confirm" onclick="confirmPeso()">Confirmar</button>
        </div>
    </div>

    <script>
        // Estado interno
        const state = { sexo: 'M', fecha: '1995-01-01', altura: 178, peso: 75 };

        // Inicializar valores en los campos ocultos
        window.onload = () => {
            document.getElementById('h-sexo').value   = state.sexo;
            document.getElementById('h-fecha').value  = state.fecha;
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
        const months = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
        const pickerState = { day: 1, month: 0, year: 1995 };

        function initPicker() {
            const dayList = document.querySelector('#wheel-day .wheel-list');
            const monthList = document.querySelector('#wheel-month .wheel-list');
            const yearList = document.querySelector('#wheel-year .wheel-list');

            // Llenar Meses
            months.forEach((m, i) => {
                const item = document.createElement('div');
                item.className = 'wheel-item';
                item.textContent = m;
                monthList.appendChild(item);
            });

            // Llenar Años (1940 - hace 12 años)
            const currentYear = new Date().getFullYear();
            const maxYear = currentYear - 12;
            for (let y = maxYear; y >= 1940; y--) {
                const item = document.createElement('div');
                item.className = 'wheel-item';
                item.textContent = y;
                yearList.appendChild(item);
            }

            // Si el año inicial (1995) no está en el rango (por si acaso), ajustar pickerState.year
            if (pickerState.year > maxYear) pickerState.year = maxYear;

            updateDays();

            // Listeners de scroll
            [
                { id: 'wheel-day', key: 'day' },
                { id: 'wheel-month', key: 'month' },
                { id: 'wheel-year', key: 'year' }
            ].forEach(col => {
                const el = document.getElementById(col.id);
                el.addEventListener('scroll', () => handleScroll(el, col.key));
            });

            // Posicionamiento inicial
            setTimeout(() => {
                setWheelValue('wheel-month', 0); // Enero
                setWheelValue('wheel-year', currentYear - 1995); // 1995
                setWheelValue('wheel-day', 0); // Día 1
            }, 100);
        }

        function updateDays() {
            const dayList = document.querySelector('#wheel-day .wheel-list');
            const daysInMonth = new Date(pickerState.year, pickerState.month + 1, 0).getDate();
            
            dayList.innerHTML = '';
            for (let d = 1; d <= daysInMonth; d++) {
                const item = document.createElement('div');
                item.className = 'wheel-item';
                item.textContent = d;
                dayList.appendChild(item);
            }
            if (pickerState.day > daysInMonth) pickerState.day = daysInMonth;
        }

        function handleScroll(el, key) {
            const items = el.querySelectorAll('.wheel-item');
            const scrollPos = el.scrollTop;
            const index = Math.round(scrollPos / 40);
            
            items.forEach((item, i) => {
                if (i === index) {
                    item.classList.add('selected');
                    if (key === 'month') {
                        pickerState.month = index;
                        updateDays();
                    } else if (key === 'year') {
                        const currentYear = new Date().getFullYear();
                        const maxYear = currentYear - 12;
                        pickerState.year = maxYear - index;
                        updateDays();
                    } else {
                        pickerState.day = index + 1;
                    }
                } else {
                    item.classList.remove('selected');
                }
            });
        }

        function setWheelValue(id, index) {
            const el = document.getElementById(id);
            el.scrollTop = index * 40;
        }

        function confirmFecha() {
            const d = pickerState.day.toString().padStart(2, '0');
            const m = (pickerState.month + 1).toString().padStart(2, '0');
            const y = pickerState.year;
            const fullDate = `${y}-${m}-${d}`;
            
            state.fecha = fullDate;
            document.getElementById('val-fecha').textContent = fullDate;
            document.getElementById('h-fecha').value = fullDate;
            
            closeSheet('fecha_nacimiento');
        }

        // Llamar init al cargar
        window.addEventListener('DOMContentLoaded', initPicker);
        function confirmAltura() {
            const v = parseInt(document.getElementById('inp-altura').value);
            if (v >= 50 && v <= 245) {
                state.altura = v;
                document.getElementById('val-altura').textContent = v + ' cm';
                document.getElementById('h-altura').value = v;
            }
            closeSheet('altura');
        }
        function confirmPeso() {
            const v = parseFloat(document.getElementById('inp-peso').value);
            if (v >= 15 && v <= 635) {
                state.peso = v;
                document.getElementById('val-peso').textContent = v + ' kg';
                document.getElementById('h-peso').value = v;
            }
            closeSheet('peso');
        }

        function submitForm() {
            // Asegurarnos de que todos los valores estén seteados
            if (!document.getElementById('h-sexo').value ||
                !document.getElementById('h-fecha').value ||
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