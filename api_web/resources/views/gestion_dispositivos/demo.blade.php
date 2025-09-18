<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Gestión Dispositivos - Demo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root { --bg:#0f172a; --panel:#1e293b; --accent:#3b82f6; --accent2:#10b981; --danger:#ef4444; --text:#f1f5f9; --muted:#94a3b8; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Ubuntu; background:var(--bg); color:var(--text); }
        header { padding:1rem 1.5rem; background:linear-gradient(90deg,#1e293b,#0f172a); display:flex; align-items:center; gap:.75rem; }
        header h1 { font-size:1.15rem; margin:0; font-weight:600; letter-spacing:.5px; }
        main { padding:1.25rem clamp(.75rem,2vw,2rem); max-width:1200px; margin:0 auto; }
        h2 { margin-top:2.5rem; font-size:1rem; text-transform:uppercase; letter-spacing:1px; font-weight:600; color:var(--muted); }
        section { margin-top:1.5rem; background:var(--panel); border:1px solid #334155; border-radius:14px; padding:1.25rem 1.25rem 1.5rem; box-shadow:0 4px 18px -6px rgba(0,0,0,.45); }
        form { display:grid; gap:1rem; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); align-items:end; }
        label { display:flex; flex-direction:column; font-size:.75rem; font-weight:600; gap:.35rem; text-transform:uppercase; letter-spacing:.5px; }
        input { background:#0f172a; border:1px solid #334155; border-radius:8px; padding:.65rem .75rem; color:var(--text); font-size:.9rem; }
        input:focus { outline:2px solid var(--accent); border-color:var(--accent); }
        button { cursor:pointer; border:none; border-radius:8px; font-weight:600; letter-spacing:.5px; font-size:.8rem; padding:.75rem 1rem; display:inline-flex; align-items:center; gap:.45rem; }
        button.primary { background:var(--accent); color:#fff; }
        button.secondary { background:#334155; color:var(--text); }
        button.danger { background:var(--danger); color:#fff; }
        button:disabled { opacity:.4; cursor:not-allowed; }
    button.mini { background:#334155; color:var(--text); padding:.35rem .55rem; font-size:.65rem; line-height:1; border-radius:6px; }
    button.mini.enlazar { background:var(--accent); }
    button.mini.eliminar { background:var(--danger); }
        table { width:100%; border-collapse:collapse; margin-top:1rem; font-size:.8rem; }
        th, td { padding:.55rem .6rem; border-bottom:1px solid #334155; vertical-align:middle; }
        th { text-align:left; font-weight:600; font-size:.65rem; letter-spacing:1px; color:var(--muted); text-transform:uppercase; }
        tr:hover td { background:#24304a; }
        .row-actions { display:flex; gap:.4rem; }
        .badge { background:#334155; padding:.25rem .55rem; border-radius:999px; font-size:.6rem; letter-spacing:.5px; text-transform:uppercase; }
        .grid { display:grid; gap:1.25rem; }
        .flex { display:flex; }
        .gap-s { gap:.5rem; }
        .status { font-size:.65rem; letter-spacing:.5px; color:var(--muted); margin-top:.5rem; min-height:1.1rem; }
        .filters { display:flex; flex-wrap:wrap; gap:.75rem; margin-top:.75rem; }
        .chip { background:#334155; padding:.35rem .7rem; border-radius:20px; font-size:.65rem; cursor:pointer; display:inline-flex; align-items:center; gap:.4rem; }
        .chip.active { background:var(--accent); }
        footer { margin-top:3rem; padding:2rem 0 3rem; text-align:center; font-size:.65rem; color:var(--muted); }
        a.inline { color:var(--accent2); text-decoration:none; }
        code { background:#0f172a; padding:.25rem .4rem; border-radius:4px; font-size:.7rem; }
        .inline-form { display:flex; flex-wrap:wrap; gap:.5rem; align-items:flex-start; }
        .inline-form input { flex:1 1 130px; min-width:120px; }
        .inline-form button { flex:0 0 auto; }
        @media (max-width: 900px){
            .acciones-grid { display:grid !important; grid-template-columns:1fr 1fr; gap:1rem; }
        }
        @media (max-width: 640px){
            .acciones-grid { grid-template-columns:1fr; }
            .inline-form { flex-direction:column; }
            .inline-form input, .inline-form button { width:100%; }
        }
        @media (max-width: 680px){ th:nth-child(5), td:nth-child(5) { display:none; } }
    </style>
</head>
<body>
<header>
    <h1>Gestión Dispositivos • Demo</h1>
</header>
<main>
    <section>
        <h2>Registrar dispositivo</h2>
        <form id="form-crear" autocomplete="off">
            <label>Nombre
                <input name="nombre" required placeholder="Sensor A" />
            </label>
            <label>MAC
                <input name="mac" required placeholder="AA:BB:CC:DD:EE:01" />
            </label>
            <label>IP
                <input name="ip" placeholder="192.168.0.50" />
            </label>
            <label>Enlace (MAC)
                <input name="enlace" placeholder="AA:BB:CC:DD:EE:02" />
            </label>
            <div>
                <button class="primary" type="submit">Crear</button>
            </div>
        </form>
        <div id="crear-status" class="status"></div>
    </section>

    <section>
        <h2>Listado / Filtros</h2>
        <div class="filters">
            <input id="filtro-prefijo" placeholder="Prefijo MAC (AA:BB)" style="flex:1;min-width:180px;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:.55rem .65rem;color:var(--text);" />
            <select id="orden-campo" style="background:#0f172a;border:1px solid #334155;border-radius:8px;padding:.55rem .65rem;color:var(--text);">
                <option value="mac">MAC</option>
                <option value="nombre">Nombre</option>
                <option value="created_at">Creado</option>
            </select>
            <select id="orden-dir" style="background:#0f172a;border:1px solid #334155;border-radius:8px;padding:.55rem .65rem;color:var(--text);">
                <option value="asc">Asc</option>
                <option value="desc">Desc</option>
            </select>
            <button class="secondary" id="btn-refrescar" type="button">Refrescar</button>
        </div>
        <div class="status" id="list-status"></div>
        <table id="tabla-dispositivos">
            <thead>
            <tr>
                <th>MAC</th>
                <th>Nombre</th>
                <th>IP</th>
                <th>Enlace</th>
                <th>Enlazado Por</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

    <section>
        <h2>Acciones rápidas</h2>
    <div class="acciones-grid" style="display:flex;flex-wrap:wrap; gap:1rem;">
            <div style="flex:1; min-width:260px;">
                <h3 style="margin:.25rem 0 .5rem;font-size:.75rem;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;">Actualizar IP</h3>
                <form id="form-ip" class="inline-form">
                    <input name="mac" placeholder="MAC" required />
                    <input name="ip" placeholder="Nueva IP" required />
                    <button class="secondary" type="submit">Actualizar</button>
                </form>
                <div class="status" id="ip-status"></div>
            </div>
            <div style="flex:1; min-width:260px;">
                <h3 style="margin:.25rem 0 .5rem;font-size:.75rem;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;">Enlazar</h3>
                <form id="form-enlace" class="inline-form">
                    <input name="mac" placeholder="MAC origen" required />
                    <input name="enlace" placeholder="MAC destino" required />
                    <button class="secondary" type="submit">Enlazar</button>
                </form>
                <div class="status" id="enlace-status"></div>
            </div>
            <div style="flex:1; min-width:260px;">
                <h3 style="margin:.25rem 0 .5rem;font-size:.75rem;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;">Quitar enlace</h3>
                <form id="form-quitar-enlace" class="inline-form">
                    <input name="mac" placeholder="MAC origen" required />
                    <button class="secondary" type="submit">Quitar</button>
                </form>
                <div class="status" id="quitar-enlace-status"></div>
            </div>
            <div style="flex:1; min-width:260px;">
                <h3 style="margin:.25rem 0 .5rem;font-size:.75rem;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;">Eliminar</h3>
                <form id="form-eliminar" class="inline-form">
                    <input name="mac" placeholder="MAC" required />
                    <button class="danger" type="submit">Eliminar</button>
                </form>
                <div class="status" id="eliminar-status"></div>
            </div>
        </div>
    </section>

    <footer>
        Demo local para probar endpoints del paquete <code>gestión_dispositivos</code>. Refresca el listado tras cada acción.
    </footer>
</main>
<script>
// La API key se inyecta desde variable de entorno del backend (APP_API_KEY_DEMO o DEMO_API_KEY)
// Define en tu .env por ejemplo: DEMO_API_KEY="<TOKEN>"
// En producción podrías sobreescribir esta vista con otra publish si no deseas exponerla.
const DEMO_API_KEY = @php
    echo json_encode(env('DEMO_API_KEY', env('APP_API_KEY_DEMO')));
@endphp;
const apiBase = '/api/dispositivos';
const $ = s => document.querySelector(s);
const qs = s => [...document.querySelectorAll(s)];

async function api(url, opts={}){
    try {
        const baseHeaders = { 'Content-Type':'application/json','Accept':'application/json' };
        if (DEMO_API_KEY) {
            baseHeaders['X-API-KEY'] = DEMO_API_KEY;
        }
        const r = await fetch(url, { headers: baseHeaders, ...opts });
        const text = await r.text();
        let data = null; try { data = text ? JSON.parse(text): null; } catch(e){ data = { raw:text }; }
        if(!r.ok) throw { status:r.status, data };
        return data;
    } catch(err){ throw err; }
}

function normalizarMac(v){ return v.trim(); }

// Cache local para resolver enlaces (por MAC directamente)
const deviceMapByMac = new Map();
let paginationMeta = null;
let inverseLinks = new Map(); // id -> array de dispositivos que lo enlazan

function buildMaps(list){
    deviceMapByMac.clear();
    inverseLinks.clear();
    list.forEach(d => { deviceMapByMac.set(d.mac, d); });
    list.forEach(d => {
        if(d.enlace_mac){
            const arr = inverseLinks.get(d.enlace_mac) || [];
            arr.push(d);
            inverseLinks.set(d.enlace_mac, arr);
        }
    });
}

function renderLinkCell(d){
    if(!d.enlace_mac) return '';
    const linked = deviceMapByMac.get(d.enlace_mac);
    if(!linked) return `<span title="Enlace MAC ${d.enlace_mac}">${d.enlace_mac}</span>`;
    const title = `Dispositivo enlazado:\nNombre: ${linked.nombre || '(sin nombre)'}\nMAC: ${linked.mac}\nIP: ${linked.ip || '-'}\nCreado: ${linked.created_at}`;
    return `<span class="badge" title="${title.replace(/"/g,'&quot;')}">${linked.nombre || linked.mac}</span>`;
}

function renderInverseLinks(mac){
    const arr = inverseLinks.get(mac);
    if(!arr || !arr.length) return '';
    const resumen = arr.map(x => x.nombre || x.mac).join(', ');
    const title = arr.map(x => `${x.mac} ${x.nombre || ''}`.trim()).join('\n');
    return `<span class="badge" title="${title.replace(/"/g,'&quot;')}">${resumen}</span>`;
}

async function cargarListado(page = 1){
    const pref = $('#filtro-prefijo').value.trim();
    const sortBy = $('#orden-campo').value;
    const sortDir = $('#orden-dir').value;
    $('#list-status').textContent = 'Cargando...';
    try {
        const q = new URLSearchParams();
        if(pref) q.set('mac_prefijo', pref);
        q.set('sort_by', sortBy); q.set('sort_dir', sortDir); q.set('per_page', 10); q.set('page', page);
        const data = await api(`${apiBase}?${q.toString()}`);
        buildMaps(data.data);
        paginationMeta = data.pagination;
        const tbody = $('#tabla-dispositivos tbody');
        tbody.innerHTML = '';
        data.data.forEach(d => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><code>${d.mac}</code></td>
                <td>${d.nombre || ''}</td>
                <td>${d.ip || ''}</td>
                <td>${renderLinkCell(d)}</td>
            <td>${renderInverseLinks(d.mac)}</td>
            <td>${d.created_at ? d.created_at.substring(0,19).replace('T',' ') : ''}</td>
                <td class='row-actions'>
                    <button data-mac="${d.mac}" class="mini enlazar" title="Preparar enlazar">🔗</button>
                    <button data-mac="${d.mac}" class="mini eliminar" title="Eliminar">🗑</button>
                </td>`;
            tbody.appendChild(tr);
        });
        $('#list-status').textContent = `Total: ${data.pagination.total} · Página ${data.pagination.current_page}/${data.pagination.last_page}`;
        renderPagination();
    } catch(e){
        $('#list-status').textContent = 'Error al cargar.';
        console.error(e);
    }
}

function renderPagination(){
    let container = document.getElementById('pagination');
    if(!container){
        container = document.createElement('div');
        container.id = 'pagination';
        container.style.marginTop = '.75rem';
        container.style.display = 'flex';
        container.style.flexWrap = 'wrap';
        container.style.gap = '.5rem';
        document.querySelector('#tabla-dispositivos').after(container);
    }
    container.innerHTML = '';
    if(!paginationMeta) return;
    const { current_page, last_page } = paginationMeta;
    const mkBtn = (label, page, disabled=false, active=false) => {
        const b = document.createElement('button');
        b.textContent = label;
        b.style.padding = '.4rem .7rem';
        b.style.fontSize = '.65rem';
        b.style.borderRadius = '6px';
        b.style.border = '1px solid #334155';
        b.style.background = active ? 'var(--accent)' : '#1e293b';
        b.style.color = active ? '#fff' : 'var(--text)';
        if(disabled){ b.disabled = true; b.style.opacity = .4; }
        if(!disabled){ b.addEventListener('click', () => cargarListado(page)); }
        return b;
    };
    container.appendChild(mkBtn('«', 1, current_page===1));
    container.appendChild(mkBtn('‹', current_page-1, current_page===1));
    // ventanas pequeñas de páginas
    const windowSize = 5;
    let start = Math.max(1, current_page - Math.floor(windowSize/2));
    let end = Math.min(last_page, start + windowSize -1);
    if(end - start + 1 < windowSize){ start = Math.max(1, end - windowSize + 1); }
    for(let p=start; p<=end; p++) container.appendChild(mkBtn(String(p), p, false, p===current_page));
    container.appendChild(mkBtn('›', current_page+1, current_page===last_page));
    container.appendChild(mkBtn('»', last_page, current_page===last_page));
}

// Crear
$('#form-crear').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    if(!payload.nombre || !payload.mac) return;
    $('#crear-status').textContent = 'Enviando...';
    try {
        const data = await api(apiBase, { method:'POST', body: JSON.stringify(payload) });
        $('#crear-status').textContent = 'Creado ID ' + data.dispositivo.id;
        e.target.reset();
        cargarListado();
    } catch(err){
        if(err.data && err.data.errors){
           const msgs = Object.values(err.data.errors).flat().join(' | ');
           $('#crear-status').textContent = 'Errores: ' + msgs;
        } else {
           $('#crear-status').textContent = 'Error: ' + (err.data && err.data.message ? err.data.message : err.status);
        }
    }
});

// Actualizar IP
$('#form-ip').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const mac = normalizarMac(fd.get('mac'));
    const ip = fd.get('ip');
    $('#ip-status').textContent = 'Actualizando...';
    try {
        await api(`${apiBase}/${encodeURIComponent(mac)}`, { method:'PUT', body: JSON.stringify({ ip }) });
        $('#ip-status').textContent = 'IP actualizada';
        cargarListado();
    } catch(err){
        if(err.data && err.data.errors){
            $('#ip-status').textContent = Object.values(err.data.errors).flat().join(' | ');
        } else {
            $('#ip-status').textContent = 'Error';
        }
    }
});

// Enlazar
$('#form-enlace').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const mac = normalizarMac(fd.get('mac'));
    const enlace = normalizarMac(fd.get('enlace'));
    $('#enlace-status').textContent = 'Enlazando...';
    try {
        await api(`${apiBase}/${encodeURIComponent(mac)}/enlace`, { method:'POST', body: JSON.stringify({ enlace }) });
        $('#enlace-status').textContent = 'Enlace establecido';
        cargarListado();
    } catch(err){
        if(err.data && err.data.errors){
            $('#enlace-status').textContent = Object.values(err.data.errors).flat().join(' | ');
        } else {
            $('#enlace-status').textContent = 'Error';
        }
    }
});

// Quitar enlace
$('#form-quitar-enlace').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const mac = normalizarMac(fd.get('mac'));
    $('#quitar-enlace-status').textContent = 'Quitando...';
    try {
        await api(`${apiBase}/${encodeURIComponent(mac)}/enlace`, { method:'DELETE' });
        $('#quitar-enlace-status').textContent = 'Enlace eliminado';
        cargarListado();
    } catch(err){
        if(err.data && err.data.errors){
            $('#quitar-enlace-status').textContent = Object.values(err.data.errors).flat().join(' | ');
        } else {
            $('#quitar-enlace-status').textContent = 'Error';
        }
    }
});

// Eliminar
$('#form-eliminar').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const mac = normalizarMac(fd.get('mac'));
    $('#eliminar-status').textContent = 'Eliminando...';
    try {
        await api(`${apiBase}/${encodeURIComponent(mac)}`, { method:'DELETE' });
        $('#eliminar-status').textContent = 'Eliminado';
        cargarListado();
    } catch(err){
        if(err.data && err.data.errors){
            $('#eliminar-status').textContent = Object.values(err.data.errors).flat().join(' | ');
        } else {
            $('#eliminar-status').textContent = 'Error';
        }
    }
});

$('#btn-refrescar').addEventListener('click', () => cargarListado());
window.addEventListener('load', () => {
    if(!DEMO_API_KEY){
        console.warn('DEMO_API_KEY no definida en .env; el demo fallará porque las rutas requieren autenticación.');
        const warn = document.createElement('div');
        warn.style.background = '#7f1d1d';
        warn.style.padding = '.75rem 1rem';
        warn.style.borderRadius = '10px';
        warn.style.margin = '1rem 0';
        warn.style.fontSize = '.7rem';
        warn.style.letterSpacing = '.5px';
        warn.textContent = 'ADVERTENCIA: Falta DEMO_API_KEY en .env (X-API-KEY no se enviará).';
        document.querySelector('main').prepend(warn);
    } else {
        const ok = document.createElement('div');
        ok.style.background = '#065f46';
        ok.style.padding = '.55rem 1rem';
        ok.style.borderRadius = '10px';
        ok.style.margin = '1rem 0';
        ok.style.fontSize = '.65rem';
        ok.style.letterSpacing = '.5px';
        ok.textContent = 'Demo usando API key (preview): ' + DEMO_API_KEY.substring(0,12) + '...';
        document.querySelector('main').prepend(ok);
    }
    cargarListado();
});

// Delegación de eventos para botones en filas (enlazar / eliminar)
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if(!btn) return;
    if(btn.classList.contains('mini') && btn.classList.contains('enlazar')){
        const mac = btn.getAttribute('data-mac');
        const form = document.getElementById('form-enlace');
        if(form){
            form.mac.value = mac;
            form.enlace.focus();
            // feedback visual opcional
            btn.animate([{ transform:'scale(1)' }, { transform:'scale(1.15)' }, { transform:'scale(1)' }], { duration:300 });
        }
    }
    if(btn.classList.contains('mini') && btn.classList.contains('eliminar')){
        const mac = btn.getAttribute('data-mac');
        if(!confirm(`¿Eliminar dispositivo ${mac}?`)) return;
        // mostrar estado rápido reutilizando eliminar-status
        const status = document.getElementById('eliminar-status');
        if(status) status.textContent = 'Eliminando...';
        try {
            await api(`${apiBase}/${encodeURIComponent(mac)}`, { method:'DELETE' });
            if(status) status.textContent = 'Eliminado (acción rápida)';
            cargarListado(paginationMeta ? paginationMeta.current_page : 1);
        } catch(err){
            if(status) status.textContent = 'Error al eliminar';
            console.error(err);
        }
    }
});
</script>
</body>
</html>
