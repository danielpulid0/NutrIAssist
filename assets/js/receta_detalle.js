// ── Checkboxes ────────────────────────────────────────────────────────
function toggleIngrediente(i) {
    const check = document.getElementById('check-' + i);
    const label = document.getElementById('ingr-name-' + i);
    const done  = check.classList.toggle('done');
    check.setAttribute('aria-checked', done);
    label.classList.toggle('done-text', done);
}

// ── Modal Swap ────────────────────────────────────────────────────────
const swapModal = document.getElementById('swapModal');
const swapTitle = document.getElementById('swapTitle');
const swapSub   = document.getElementById('swapSubtitle');
const swapList  = document.getElementById('swapOptionsList');

let _ing = '', _gr = 0, _idx = -1;

function abrirSwap(nombre, gramos, idx) {
    _ing = nombre; _gr = gramos; _idx = idx;
    swapTitle.textContent = 'Sustituir ' + nombre;
    swapSub.textContent   = 'Buscando alternativas...';
    swapList.innerHTML    = '<li class="swap-loading">⏳ Gemma está analizando alternativas...</li>';
    swapModal.classList.add('active');
    pedirSwap(nombre, gramos);
}

function pedirSwap(nombre, gramos) {
    fetch('../controllers/ia_swap.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ ingrediente: nombre, gramos: gramos })
    })
    .then(r => r.json())
    .then(json => {
        if (json.status === 'success') {
            swapSub.textContent  = json.subtitulo || 'Sugerencias basadas en tus macros:';
            swapList.innerHTML   = '';
            window._swapOpciones = json.data;

            json.data.forEach((op, idx) => {
                const li = document.createElement('li');
                li.className = 'swap-option';
                li.innerHTML = `
                    <div class="swap-option-info">
                        <div class="swap-option-top">
                            <span class="swap-nombre">${esc(op.nombre)}</span>
                            ${op.recomendado ? '<span class="badge-recomendado">RECOMENDADO</span>' : ''}
                        </div>
                        <div class="swap-nota">${esc(op.nota)}</div>
                    </div>
                    <button class="btn-elegir" onclick="elegirSwap(${idx})">Elegir</button>
                `;
                swapList.appendChild(li);
            });
        } else {
            swapList.innerHTML = `
                <li style="padding:1rem 0;text-align:center;color:#EF4444">
                    ⚠️ ${esc(json.message || 'Error')}
                    <br><button onclick="pedirSwap('${esc(_ing)}',${_gr})"
                        style="margin-top:0.5rem;background:none;border:1px solid #ddd;
                               border-radius:8px;padding:0.3rem 0.8rem;cursor:pointer">
                        🔄 Reintentar</button>
                </li>`;
        }
    })
    .catch(() => {
        swapList.innerHTML = `
            <li style="padding:1rem 0;text-align:center;color:#EF4444">
                ⚠️ Error de conexión
                <br><button onclick="pedirSwap('${esc(_ing)}',${_gr})"
                    style="margin-top:0.5rem;background:none;border:1px solid #ddd;
                           border-radius:8px;padding:0.3rem 0.8rem;cursor:pointer">
                    🔄 Reintentar</button>
            </li>`;
    });
}

function elegirSwap(idx) {
    const op = (window._swapOpciones || [])[idx];
    if (!op) return;
    cerrarSwap();

    // Actualizar DOM del ingrediente en pantalla
    if (_idx >= 0) {
        const nameEl = document.getElementById('ingr-name-' + _idx);
        const metaEl = document.getElementById('ingr-meta-' + _idx);
        if (nameEl) {
            nameEl.textContent = _gr + 'g ' + op.nombre;
            nameEl.style.color = 'var(--color-malachite)';
            nameEl.style.transition = 'color 0.3s';
        }
        if (metaEl) {
            metaEl.textContent = op.calorias + ' kcal • ' + op.proteina + 'g prot';
        }
    }
    const t = document.createElement('div');
    t.textContent = '✓ Sustituido por: ' + op.nombre;
    Object.assign(t.style, {
        position:'fixed', bottom:'110px', left:'50%', transform:'translateX(-50%)',
        background:'#1F2937', color:'#fff', padding:'0.6rem 1.2rem',
        borderRadius:'99px', fontSize:'0.85rem', fontWeight:'500',
        zIndex:'2000', whiteSpace:'nowrap', transition:'opacity 0.3s'
    });
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2200);
}

function esc(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function cerrarSwap() { swapModal.classList.remove('active'); }
swapModal.addEventListener('click', e => { if (e.target === swapModal) cerrarSwap(); });

// ── Modal Registro Receta ─────────────────────────────────────────────
const registroModal = document.getElementById('registroModal');

function abrirRegistro() { registroModal.classList.add('active'); }
function cerrarRegistro() { registroModal.classList.remove('active'); }
registroModal.addEventListener('click', e => { if (e.target === registroModal) cerrarRegistro(); });

async function guardarReceta(tipo) {
    cerrarRegistro();
    const btn = document.querySelector('.btn-registrar');
    btn.disabled = true;
    btn.innerHTML = '⏳ Guardando...';

    try {
        const resp = await fetch('../controllers/guardar_comida_manual.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo_comida: tipo,
                fecha:       new Date().toISOString().split('T')[0],
                alimento:    window.NutriRecetaActual.titulo,
                calorias:    window.NutriRecetaActual.calorias,
                proteina:    window.NutriRecetaActual.proteina,
                carbs:       window.NutriRecetaActual.carbs,
                grasas:      window.NutriRecetaActual.grasas,
            })
        });
        const json = await resp.json();

        if (json.status === 'success') {
            btn.innerHTML = '✓ Registrado en ' + tipo;
            btn.style.background = '#0db844';
            setTimeout(() => window.location.href = 'diario.php', 1200);
        } else {
            btn.disabled = false;
            btn.innerHTML = '⚠️ Error. Intenta de nuevo';
            btn.style.background = '#EF4444';
            setTimeout(() => {
                btn.innerHTML = '🗓️ Registrar Comida';
                btn.style.background = '';
                btn.disabled = false;
            }, 2500);
        }
    } catch {
        btn.disabled = false;
        btn.innerHTML = '⚠️ Error de conexión';
        setTimeout(() => { btn.innerHTML = '🗓️ Registrar Comida'; btn.disabled = false; }, 2500);
    }
}
