// ─── UTILIDADES DE FECHA ──────────────────────────────────────────
function fmtDate(d) {
    // Devuelve YYYY-MM-DD de un objeto Date, evitando problemas de zona horaria
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function addDays(dateStr, n) {
    // Suma n días a una fecha en formato YYYY-MM-DD
    const d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() + n);
    return fmtDate(d);
}

function sundayOf(dateStr) {
    // Devuelve el domingo de la semana que contiene dateStr
    const d = new Date(dateStr + 'T12:00:00');
    d.setDate(d.getDate() - d.getDay()); // retrocede al domingo
    return fmtDate(d);
}

// ─── RENDERIZADO DE UNA SEMANA ────────────────────────────────────
function renderWeekSlide(el, domingoStr) {
    let html = '';
    for (let i = 0; i < 7; i++) {
        const fechaDia = addDays(domingoStr, i);
        const esFut   = fechaDia > window.NutriConfig.FECHA_HOY;
        const esHoy   = fechaDia === window.NutriConfig.FECHA_HOY;
        const esSel   = fechaDia === window.NutriConfig.FECHA_SEL;
        const numDia  = parseInt(fechaDia.split('-')[2], 10);

        let cls = 'cal-day';
        if (esFut)        cls += ' future';
        if (esHoy && !esSel) cls += ' today';
        if (esSel)        cls += ' active';

        const onclick = esFut ? '' : `onclick="irAFecha('${fechaDia}')"`;
        html += `<div class="${cls}" ${onclick}>
                    <div class="cal-day-num">${numDia}</div>
                 </div>`;
    }
    el.innerHTML = html;
}

// ─── CALENDARIO SEMANAL con SWIPE ────────────────────────────────
const track     = document.getElementById('cal-week-track');
const slidePrev = document.getElementById('slide-prev');
const slideCurr = document.getElementById('slide-curr');
const slideNext = document.getElementById('slide-next');
const mesLabel  = document.getElementById('cal-mes-label');
const btnPrev   = document.getElementById('btn-prev-mes');

let semanaActual = sundayOf(window.NutriConfig.FECHA_SEL); // domingo de la semana visible

function actualizarCalendario() {
    const dom_prev = addDays(semanaActual, -7);
    const dom_next = addDays(semanaActual,  7);

    renderWeekSlide(slidePrev, dom_prev);
    renderWeekSlide(slideCurr, semanaActual);
    renderWeekSlide(slideNext, dom_next);

    // Posicionar track en la slide central (sin animación)
    track.style.transition = 'none';
    track.style.transform  = 'translateX(-100%)';

    // Actualizar label del mes (según la fecha de jueves de la semana, para estabilidad)
    const jueves = addDays(semanaActual, 4);
    const [y, m] = jueves.split('-');
    mesLabel.textContent = window.NutriConfig.MESES[parseInt(m, 10) - 1] + ' ' + y;
}

function irAFecha(fecha) {
    if (fecha > window.NutriConfig.FECHA_HOY) return;
    window.location.href = 'diario.php?fecha=' + fecha;
}

// Swipe
let touchStartX = 0;
const wrapper = document.getElementById('cal-week-wrapper');

// ── Swipe táctil (móvil) ─────────────────────────────────────────
if (wrapper) {
    wrapper.addEventListener('touchstart', e => {
        touchStartX = e.touches[0].clientX;
    }, { passive: true });

    wrapper.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - touchStartX;
        navegarPorDelta(dx);
    }, { passive: true });

    // ── Swipe con trackpad de laptop (wheel horizontal) ───────────────
    let wheelDebounce = null;
    let wheelAcum     = 0;

    wrapper.addEventListener('wheel', e => {
        // Solo reaccionar a scroll horizontal pronunciado
        if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) return;
        e.preventDefault();

        wheelAcum += e.deltaX;

        // Debounce: espera a que el gesto termine antes de navegar
        clearTimeout(wheelDebounce);
        wheelDebounce = setTimeout(() => {
            if (Math.abs(wheelAcum) > 60) {
                // deltaX positivo = deslizó a la izquierda (quiere avanzar)
                // deltaX negativo = deslizó a la derecha (quiere retroceder)
                navegarPorDelta(wheelAcum > 0 ? -80 : 80); // invierte para coincidir con touch
            }
            wheelAcum = 0;
        }, 120);
    }, { passive: false });
}

// Función compartida por touch y wheel
function navegarPorDelta(dx) {
    if (Math.abs(dx) < 50) return;

    if (dx > 0) {
        // Retroceder → semana anterior
        const nuevaFecha = addDays(window.NutriConfig.FECHA_SEL, -7);
        const clamped    = nuevaFecha > window.NutriConfig.FECHA_HOY ? window.NutriConfig.FECHA_HOY : nuevaFecha;
        track.style.transition = 'transform 0.3s ease';
        track.style.transform  = 'translateX(0%)';
        track.addEventListener('transitionend', function go() {
            track.removeEventListener('transitionend', go);
            irAFecha(clamped);
        });
    } else {
        // Avanzar → semana siguiente (solo si no es futura)
        const siguienteFecha = addDays(window.NutriConfig.FECHA_SEL, 7);
        if (siguienteFecha <= window.NutriConfig.FECHA_HOY) {
            track.style.transition = 'transform 0.3s ease';
            track.style.transform  = 'translateX(-200%)';
            track.addEventListener('transitionend', function go() {
                track.removeEventListener('transitionend', go);
                irAFecha(siguienteFecha);
            });
        } else {
            // Rebote visual — no hay semanas futuras
            track.style.transition = 'transform 0.2s ease';
            track.style.transform  = 'translateX(-115%)';
            setTimeout(() => { track.style.transform = 'translateX(-100%)'; }, 200);
        }
    }
}

// Botón mes anterior → ir al mismo día un mes atrás
if (btnPrev) {
    btnPrev.addEventListener('click', () => {
        const d = new Date(window.NutriConfig.FECHA_SEL + 'T12:00:00');
        d.setMonth(d.getMonth() - 1);
        let nueva = fmtDate(d);
        if (nueva > window.NutriConfig.FECHA_HOY) nueva = window.NutriConfig.FECHA_HOY;
        irAFecha(nueva);
    });
}

// Inicializar
if (track) {
    actualizarCalendario();
}

// ─── ACORDEÓN ────────────────────────────────────────────────────
window.toggleMeal = function(id) {
    const content = document.getElementById('meal-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const header  = document.getElementById('hdr-' + id);
    if (!content) return;
    const isOpen = content.classList.toggle('open');
    if (chevron) chevron.classList.toggle('up', isOpen);
    if (header)  header.setAttribute('aria-expanded', isOpen);
};

// ══════════════════════════════════════════════════════════════════
// MODAL — Registro de alimentos en lista (2 pasos)
// ══════════════════════════════════════════════════════════════════

// ─── Estado ──────────────────────────────────────────────────────
let modalTipo  = '';
let modalFecha = window.NutriConfig.FECHA_SEL;
let listaItems = [];       // [{nombre, id_alimento, gramos, cal_100, prot_100, c_100, g_100}]
let itemTemp   = null;     // resultado seleccionado del dropdown (pendiente de agregar)

// ─── Referencias DOM ─────────────────────────────────────────────
const overlay     = document.getElementById('modal-overlay');
const vistaLista  = document.getElementById('vista-lista');
const vistaPreview= document.getElementById('vista-preview');
const inputBusq   = document.getElementById('input-busqueda');
const inputQty    = document.getElementById('input-cantidad');
const btnAddAlim  = document.getElementById('btn-add-alim');
const itemsList   = document.getElementById('items-list');
const itemsEmpty  = document.getElementById('items-empty');
const btnReg      = document.getElementById('btn-registrar');
const acDrop      = document.getElementById('ac-dropdown');

// ─── Abrir / cerrar modal ─────────────────────────────────────────
window.abrirModal = function(tipo) {
    modalTipo  = tipo;
    modalFecha = window.NutriConfig.FECHA_SEL;
    listaItems = [];
    itemTemp   = null;
    document.getElementById('modal-titulo').textContent = 'Agregar a ' + tipo;
    mostrarVista('lista');
    renderLista();
    overlay.classList.add('active');
    requestAnimationFrame(() => inputBusq.focus());
};

function cerrarModal() {
    overlay.classList.remove('active');
    inputBusq.value  = '';
    if (inputQty) inputQty.value = '';
    acDrop.classList.remove('open');
    acDrop.innerHTML = '';
    itemTemp = null;
    actualizarBtnAdd();
}

if (overlay) {
    overlay.addEventListener('click', e => { if (e.target === overlay) cerrarModal(); });
    document.getElementById('btn-modal-close')?.addEventListener('click',  cerrarModal);
    document.getElementById('btn-modal-close-2')?.addEventListener('click', cerrarModal);
}

// ─── Cambio de vista ──────────────────────────────────────────────
function mostrarVista(cual) {
    if (vistaLista) vistaLista.classList.toggle('active', cual === 'lista');
    if (vistaPreview) vistaPreview.classList.toggle('active', cual === 'preview');
}

// ─── Render de la lista de ítems ─────────────────────────────────
function renderLista() {
    if (!itemsList) return;
    const tieneItems = listaItems.length > 0;
    if (itemsEmpty) itemsEmpty.style.display = tieneItems ? 'none' : 'block';
    if (btnReg) btnReg.disabled = !tieneItems;

    // Limpiar ítems previos (conservar el mensaje vacío)
    Array.from(itemsList.querySelectorAll('.item-row')).forEach(el => el.remove());

    listaItems.forEach((it, idx) => {
        const row = document.createElement('div');
        row.className = 'item-row';
        row.innerHTML = `
            <div class="item-dot"></div>
            <div class="item-name" title="${escH(it.nombre)}">${escH(it.nombre)}</div>
            <div class="item-qty">${it.gramos} g</div>
            <button class="btn-remove-item" title="Eliminar" data-idx="${idx}">✕</button>
        `;
        itemsList.appendChild(row);
    });

    // Delegación de eventos para eliminar
    itemsList.querySelectorAll('.btn-remove-item').forEach(btn => {
        btn.addEventListener('click', () => {
            listaItems.splice(parseInt(btn.dataset.idx), 1);
            renderLista();
        });
    });
}

// ─── Autocompletado ───────────────────────────────────────────────
let acTimer = null;

function actualizarBtnAdd() {
    if (!inputQty || !btnAddAlim) return;
    const qty = parseFloat(inputQty.value);
    btnAddAlim.disabled = !(itemTemp && qty > 0);
}

if (inputBusq) {
    inputBusq.addEventListener('input', () => {
        // Al escribir de nuevo, reseteamos la selección temporal
        itemTemp = null;
        actualizarBtnAdd();
        clearTimeout(acTimer);
        const q = inputBusq.value.trim();
        if (q.length < 2) { acDrop.classList.remove('open'); return; }
        acDrop.innerHTML = '<div class="ac-status">Buscando…</div>';
        acDrop.classList.add('open');
        acTimer = setTimeout(() => buscarAlimento(q), 450);
    });
}

if (inputQty) inputQty.addEventListener('input', actualizarBtnAdd);

async function buscarAlimento(query) {
    if (!acDrop) return;
    try {
        const resp = await fetch(
            `../controllers/api_alimentos.php?query=${encodeURIComponent(query)}`,
            { credentials: 'same-origin' }
        );
        const json = await resp.json();

        if (json.status !== 'success' || !json.data) {
            acDrop.innerHTML = `<div class="ac-status">Sin resultados para "${escH(query)}".<br>
                <a href="chat_ia.php">Prueba en tu chat NutrIAssist →</a></div>`;
            return;
        }

        const d    = json.data;
        const kcal = Math.round(parseFloat(d.calorias_por_100g) || 0);
        const prot = parseFloat(d.proteina_por_100g || 0).toFixed(1);
        const carb = parseFloat(d.carbs_por_100g    || 0).toFixed(1);
        const gras = parseFloat(d.grasas_por_100g   || 0).toFixed(1);
        const badge = json.fuente === 'mysql_local'
            ? '<span class="ac-badge local">Local</span>'
            : '<span class="ac-badge usda">USDA</span>';
        const displayName = d.nombre ?? json.nombre_canonico ?? query;

        acDrop.innerHTML = `
            <div class="ac-item" id="ac-result" tabindex="0" role="option">
                <span class="ac-item-icon">🥗</span>
                <div class="ac-item-info">
                    <div class="ac-item-name">${escH(displayName)}</div>
                    <div class="ac-item-sub">${kcal} kcal · P ${prot}g · C ${carb}g · G ${gras}g <small>(por 100g)</small></div>
                </div>
                ${badge}
            </div>
        `;

        const acResult = document.getElementById('ac-result');
        if (acResult) {
            const seleccionar = () => {
                itemTemp = {
                    nombre:    displayName,
                    id_alimento: d.id_alimento ?? null,
                    cal_100:   parseFloat(d.calorias_por_100g) || 0,
                    prot_100:  parseFloat(d.proteina_por_100g) || 0,
                    c_100:     parseFloat(d.carbs_por_100g)    || 0,
                    g_100:     parseFloat(d.grasas_por_100g)   || 0,
                };
                inputBusq.value = displayName;
                acDrop.classList.remove('open');
                actualizarBtnAdd();
                inputQty.focus();
            };
            acResult.addEventListener('click', seleccionar);
            acResult.addEventListener('keydown', ev => {
                if (ev.key === 'Enter' || ev.key === ' ') seleccionar();
            });
        }

    } catch {
        acDrop.innerHTML = '<div class="ac-status">Error de conexión. Inténtalo de nuevo.</div>';
    }
}

// Cerrar dropdown al clic fuera
document.addEventListener('click', e => {
    const wrap = document.getElementById('search-wrap');
    if (wrap && !wrap.contains(e.target) && acDrop) {
        acDrop.classList.remove('open');
    }
});

// ─── Agregar ítem a la lista ──────────────────────────────────────
if (btnAddAlim) {
    btnAddAlim.addEventListener('click', () => {
        if (!itemTemp) return;
        const gramos = parseFloat(inputQty.value);
        if (!(gramos > 0)) { mostrarToast('Ingresa una cantidad válida'); return; }

        listaItems.push({
            nombre:      itemTemp.nombre,
            id_alimento: itemTemp.id_alimento,
            gramos:      gramos,
            cal_100:     itemTemp.cal_100,
            prot_100:    itemTemp.prot_100,
            c_100:       itemTemp.c_100,
            g_100:       itemTemp.g_100,
        });

        // Reset campo de entrada
        inputBusq.value  = '';
        inputQty.value   = '';
        itemTemp         = null;
        acDrop.classList.remove('open');
        acDrop.innerHTML = '';
        actualizarBtnAdd();
        renderLista();
        inputBusq.focus();
    });
}

// Enter en cantidad → agregar
if (inputQty) {
    inputQty.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); btnAddAlim.click(); }
    });
}

// ─── Paso 1 → Paso 2: construir preview ──────────────────────────
if (btnReg) {
    btnReg.addEventListener('click', () => {
        if (listaItems.length === 0) return;

        const tbody = document.getElementById('preview-tbody');
        if (!tbody) return;
        tbody.innerHTML = '';

        let totCal = 0, totProt = 0, totC = 0, totG = 0;

        listaItems.forEach(it => {
            const f    = it.gramos / 100;
            const cal  = Math.round(it.cal_100  * f);
            const prot = +(it.prot_100 * f).toFixed(1);
            const c    = +(it.c_100   * f).toFixed(1);
            const g    = +(it.g_100   * f).toFixed(1);

            // Guardar macros calculados en el ítem para el POST
            it.calorias_ia = cal;
            it.proteina_ia = prot;
            it.carbs_ia    = c;
            it.grasas_ia   = g;

            totCal  += cal;
            totProt += prot;
            totC    += c;
            totG    += g;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="td-name" title="${escH(it.nombre)}">${escH(it.nombre)}</td>
                <td class="td-qty">${it.gramos}</td>
                <td>${cal}</td>
                <td>${prot}</td>
                <td>${c}</td>
                <td>${g}</td>
            `;
            tbody.appendChild(tr);
        });

        // Fila de totales
        const trTotal = document.createElement('tr');
        trTotal.className = 'total-row';
        trTotal.innerHTML = `
            <td class="td-name">TOTAL</td>
            <td>—</td>
            <td>${totCal}</td>
            <td>${totProt.toFixed(1)}</td>
            <td>${totC.toFixed(1)}</td>
            <td>${totG.toFixed(1)}</td>
        `;
        tbody.appendChild(trTotal);

        // Actualizar header de la vista previa
        const hoyLabel = modalFecha === window.NutriConfig.FECHA_HOY ? 'Hoy' : modalFecha;
        const previewHeader = document.getElementById('preview-header');
        if (previewHeader) {
            previewHeader.textContent = `${modalTipo} — ${hoyLabel}`;
        }

        mostrarVista('preview');
    });
}

// ─── Paso 2 → Paso 1: editar ─────────────────────────────────────
const btnEditar = document.getElementById('btn-editar');
if (btnEditar) {
    btnEditar.addEventListener('click', () => {
        mostrarVista('lista');
    });
}

// ─── Confirmar → POST ────────────────────────────────────────────
const btnConfirmar = document.getElementById('btn-confirmar');
if (btnConfirmar) {
    btnConfirmar.addEventListener('click', async function() {
        this.disabled    = true;
        this.textContent = 'Guardando…';

        const payload = {
            tipo_comida: modalTipo,
            fecha:       modalFecha,
            items:       listaItems.map(it => ({
                nombre:      it.nombre,
                id_alimento: it.id_alimento,
                gramos:      it.gramos,
                calorias_ia: it.calorias_ia,
                proteina_ia: it.proteina_ia,
                carbs_ia:    it.carbs_ia,
                grasas_ia:   it.grasas_ia,
            })),
        };

        try {
            const resp = await fetch('../controllers/guardar_comida_lista.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
                credentials: 'same-origin',
            });
            const json = await resp.json();

            if (json.status === 'success') {
                cerrarModal();
                mostrarToast('✓ Comida registrada');
                setTimeout(() => location.reload(), 900);
            } else {
                mostrarToast('Error: ' + (json.message || 'Inténtalo de nuevo'));
                this.disabled    = false;
                this.textContent = '✓ Confirmar';
            }
        } catch {
            mostrarToast('Error de conexión');
            this.disabled    = false;
            this.textContent = '✓ Confirmar';
        }
    });
}

// ─── Utilidades ───────────────────────────────────────────────────
function escH(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

window.mostrarToast = function(msg) {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
};
