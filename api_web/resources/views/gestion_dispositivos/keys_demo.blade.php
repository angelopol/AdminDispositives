<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Gestión API Keys - Demo</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        :root { --bg:#0f172a; --panel:#1e293b; --border:#334155; --accent:#6366f1; --accent2:#0ea5e9; --danger:#dc2626; --ok:#059669; --text:#f1f5f9; --muted:#94a3b8; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,"Segoe UI",Roboto,Ubuntu; background:var(--bg); color:var(--text); }
        header { padding:1rem 1.25rem; background:linear-gradient(90deg,#1e293b,#0f172a); display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        header h1 { margin:0; font-size:1.05rem; letter-spacing:.5px; }
        main { max-width:1200px; margin:0 auto; padding:1.25rem clamp(.75rem,2vw,1.75rem); }
        section { background:var(--panel); border:1px solid var(--border); padding:1.1rem 1.2rem 1.4rem; border-radius:14px; margin-top:1.4rem; box-shadow:0 4px 18px -6px rgba(0,0,0,.45); }
        h2 { margin:0 0 .75rem; font-size:.8rem; text-transform:uppercase; letter-spacing:1px; color:var(--muted); }
        form { display:grid; gap:.9rem; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); align-items:end; }
        label { display:flex; flex-direction:column; gap:.35rem; font-size:.65rem; letter-spacing:.5px; font-weight:600; text-transform:uppercase; }
        input[type=text] { background:#0f172a; border:1px solid var(--border); padding:.6rem .7rem; border-radius:8px; color:var(--text); font-size:.85rem; }
        input:focus { outline:2px solid var(--accent); border-color:var(--accent); }
        table { width:100%; border-collapse:collapse; margin-top:1rem; font-size:.75rem; }
        th,td { padding:.55rem .55rem; border-bottom:1px solid var(--border); vertical-align:middle; }
        th { text-align:left; font-size:.6rem; letter-spacing:.85px; text-transform:uppercase; color:var(--muted); font-weight:600; }
        tr:hover td { background:#24304a; }
        button { cursor:pointer; border:none; border-radius:8px; font-weight:600; letter-spacing:.5px; font-size:.7rem; padding:.55rem .8rem; display:inline-flex; gap:.4rem; align-items:center; }
        button.primary { background:var(--accent); color:#fff; }
        button.secondary { background:#334155; color:var(--text); }
        button.danger { background:var(--danger); color:#fff; }
        button.outline { background:transparent; border:1px solid var(--border); color:var(--text); }
        button.small { padding:.35rem .55rem; font-size:.6rem; border-radius:6px; }
        button:disabled { opacity:.45; cursor:not-allowed; }
        .status { font-size:.6rem; letter-spacing:.5px; color:var(--muted); margin-top:.5rem; min-height:1rem; }
        .badge { background:#334155; padding:.25rem .55rem; border-radius:999px; font-size:.55rem; letter-spacing:.5px; text-transform:uppercase; }
        .badge.green { background:var(--ok); color:#fff; }
        .badge.red { background:var(--danger); color:#fff; }
        .flex { display:flex; }
        .gap-s { gap:.5rem; }
        code { background:#0f172a; padding:.25rem .45rem; border-radius:6px; font-size:.65rem; }
        .grid { display:grid; gap:1rem; }
        @media (max-width:780px){ th:nth-child(7), td:nth-child(7){ display:none; } }
    </style>
</head>
<body>
<header>
    <h1>Demo Gestión API Keys</h1>
    <div id="header-info" style="font-size:.55rem; letter-spacing:.5px; color:var(--muted);"></div>
</header>
<main>
    <section>
        <h2>Crear nueva llave</h2>
        <form id="form-crear" autocomplete="off">
            <label>Nombre
                <input name="name" type="text" required placeholder="Servicio X" />
            </label>
            <label style="display:flex; align-items:center; gap:.5rem; flex-direction:row; font-weight:500; text-transform:none; font-size:.7rem;">
                <input name="is_admin" type="checkbox" style="width:16px;height:16px;" /> Admin
            </label>
            <div style="display:flex; gap:.5rem;">
                <button class="primary" type="submit">Crear</button>
                <button class="outline" type="button" id="btn-refrescar">Refrescar</button>
            </div>
        </form>
        <div class="status" id="crear-status"></div>
        <div id="token-creado" class="status" style="color:var(--accent2);"></div>
    </section>

    <section>
        <h2>Listado</h2>
        <div class="status" id="list-status">Cargando...</div>
        <table id="tabla-keys">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Preview</th>
                <th>Admin</th>
                <th>Active</th>
                <th>Last Used</th>
                <th>Created</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>

    <section>
        <h2>Rotar / Activar / Desactivar</h2>
        <form id="form-actualizar" class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
            <label>ID
                <input name="id" type="text" placeholder="ID" required />
            </label>
            <label>Nuevo Nombre (opcional)
                <input name="name" type="text" placeholder="Nombre nuevo" />
            </label>
            <label style="flex-direction:row; align-items:center; font-size:.65rem; text-transform:none; font-weight:500;">
                <input name="active" type="checkbox" style="width:16px;height:16px;" /> Active
            </label>
            <label style="flex-direction:row; align-items:center; font-size:.65rem; text-transform:none; font-weight:500;">
                <input name="is_admin" type="checkbox" style="width:16px;height:16px;" /> Admin
            </label>
            <label style="flex-direction:row; align-items:center; font-size:.65rem; text-transform:none; font-weight:500;">
                <input name="rotate" type="checkbox" style="width:16px;height:16px;" /> Rotar
            </label>
            <div style="display:flex; gap:.5rem; align-items:center;">
                <button class="secondary" type="submit">Aplicar</button>
                <button class="outline" type="button" id="btn-limpiar-act">Limpiar</button>
            </div>
        </form>
        <div class="status" id="actualizar-status"></div>
        <div id="rotated-token" class="status" style="color:var(--accent2);"></div>
    </section>

    <section>
        <h2>Eliminar</h2>
        <form id="form-eliminar" style="display:flex; gap:.6rem; flex-wrap:wrap; align-items:flex-end;">
            <label>ID
                <input name="id" type="text" required placeholder="ID" />
            </label>
            <button class="danger" type="submit">Eliminar</button>
        </form>
        <div class="status" id="eliminar-status"></div>
    </section>

    <footer style="margin-top:3rem; text-align:center; font-size:.55rem; color:var(--muted); padding:2rem 0 3rem;">Demo administración API Keys (usa ADMIN_API_KEY del .env)</footer>
</main>
<script>
const ADMIN_API_KEY = @php echo json_encode(env('ADMIN_API_KEY')); @endphp;
const baseKeys = '/api/keys';
const $ = s => document.querySelector(s);

function headers(){
    const h = { 'Content-Type':'application/json','Accept':'application/json' };
    if(ADMIN_API_KEY){ h['X-API-KEY'] = ADMIN_API_KEY; }
    return h;
}

async function req(url, opts={}){
    const r = await fetch(url, { headers: headers(), ...opts });
    const text = await r.text();
    let data = null; try{ data = text? JSON.parse(text): null; }catch(e){ data = { raw:text }; }
    if(!r.ok) throw { status:r.status, data };
    return data;
}

function renderRow(k){
    return `<tr data-id="${k.id}">
        <td>${k.id}</td>
        <td>${k.name || ''}</td>
        <td><code>${k.plain_preview}</code></td>
        <td>${k.is_admin ? '<span class="badge green">YES</span>' : '<span class="badge">NO</span>'}</td>
        <td>${k.active ? '<span class="badge green">ON</span>' : '<span class="badge red">OFF</span>'}</td>
        <td>${k.last_used_at ? k.last_used_at.replace('T',' ').substring(0,19) : ''}</td>
        <td>${k.created_at ? k.created_at.replace('T',' ').substring(0,19) : ''}</td>
        <td style="display:flex; gap:.35rem; flex-wrap:wrap;">
            <button class="small outline" data-act="edit" data-id="${k.id}">Editar</button>
            <button class="small danger" data-act="del" data-id="${k.id}">Del</button>
            <button class="small secondary" data-act="rotar" data-id="${k.id}">Rotar</button>
            <button class="small secondary" data-act="toggle" data-id="${k.id}" data-active="${k.active?1:0}">${k.active? 'Off':'On'}</button>
        </td>
    </tr>`;
}

async function cargar(){
    $('#list-status').textContent = 'Cargando...';
    try {
        const data = await req(baseKeys);
        const tbody = $('#tabla-keys tbody');
        tbody.innerHTML = data.data.map(renderRow).join('');
        $('#list-status').textContent = 'Total: ' + data.data.length;
    } catch(e){
        $('#list-status').textContent = 'Error cargando (' + (e.status||'') + ')';
        console.error(e);
    }
}

// Crear
$('#form-crear').addEventListener('submit', async e => {
    e.preventDefault();
    $('#crear-status').textContent = 'Creando...';
    $('#token-creado').textContent = '';
    const fd = new FormData(e.target);
    const payload = { name: fd.get('name') };
    if(fd.get('is_admin')) payload.is_admin = true;
    try {
        const data = await req(baseKeys, { method:'POST', body: JSON.stringify(payload) });
        $('#crear-status').textContent = 'Creada (ID ' + data.api_key.id + ')';
        $('#token-creado').textContent = 'Token (copiar ahora): ' + data.token_plain;
        e.target.reset();
        cargar();
    } catch(e){
        $('#crear-status').textContent = 'Error: ' + (e.data?.message || e.status);
    }
});

// Actualizar / rotar / cambiar flags desde formulario general
$('#form-actualizar').addEventListener('submit', async e => {
    e.preventDefault();
    $('#actualizar-status').textContent = 'Aplicando...';
    $('#rotated-token').textContent = '';
    const fd = new FormData(e.target);
    const id = fd.get('id');
    const payload = {};
    if(fd.get('name')) payload.name = fd.get('name');
    if(fd.get('active') !== null) payload.active = !!fd.get('active');
    if(fd.get('is_admin') !== null) payload.is_admin = !!fd.get('is_admin');
    if(fd.get('rotate')) payload.rotate = true;
    try {
        const data = await req(`${baseKeys}/${encodeURIComponent(id)}`, { method:'PUT', body: JSON.stringify(payload) });
        $('#actualizar-status').textContent = 'Actualizado';
        if(data.rotated_token_plain){
            $('#rotated-token').textContent = 'Nuevo token: ' + data.rotated_token_plain;
        }
        cargar();
    } catch(e){
        $('#actualizar-status').textContent = 'Error: ' + (e.data?.message || e.status);
    }
});

$('#btn-limpiar-act').addEventListener('click', () => {
    const f = document.getElementById('form-actualizar');
    f.reset();
    $('#actualizar-status').textContent = '';
    $('#rotated-token').textContent = '';
});

// Eliminar
$('#form-eliminar').addEventListener('submit', async e => {
    e.preventDefault();
    $('#eliminar-status').textContent = 'Eliminando...';
    const fd = new FormData(e.target);
    const id = fd.get('id');
    try {
        await req(`${baseKeys}/${encodeURIComponent(id)}`, { method:'DELETE' });
        $('#eliminar-status').textContent = 'Eliminada';
        e.target.reset();
        cargar();
    } catch(e){
        $('#eliminar-status').textContent = 'Error: ' + (e.data?.message || e.status);
    }
});

// Acciones inline en tabla
document.addEventListener('click', async e => {
    const btn = e.target.closest('button');
    if(!btn || !btn.dataset.act) return;
    const id = btn.dataset.id;
    if(btn.dataset.act === 'del'){
        if(!confirm('¿Eliminar key ' + id + '?')) return;
        try { await req(`${baseKeys}/${encodeURIComponent(id)}`, { method:'DELETE' }); cargar(); } catch(err){ alert('Error eliminando'); }
    }
    if(btn.dataset.act === 'edit'){
        // Cargar datos en formulario actualizar (fetch ya tenemos en tabla)
        const tr = btn.closest('tr');
        if(tr){
            const f = document.getElementById('form-actualizar');
            f.id.value = id;
            f.name.value = tr.children[1].textContent.trim();
            f.active.checked = tr.children[4].textContent.includes('ON');
            f.is_admin.checked = tr.children[3].textContent.includes('YES');
            f.rotate.checked = false;
            window.scrollTo({ top: f.getBoundingClientRect().top + window.scrollY - 40, behavior:'smooth' });
        }
    }
    if(btn.dataset.act === 'rotar'){
        try {
            const data = await req(`${baseKeys}/${encodeURIComponent(id)}`, { method:'PUT', body: JSON.stringify({ rotate:true }) });
            if(data.rotated_token_plain){ alert('Nuevo token (copiar ahora):\n' + data.rotated_token_plain); }
            cargar();
        } catch(err){ alert('Error rotando'); }
    }
    if(btn.dataset.act === 'toggle'){
        const newActive = btn.dataset.active === '1' ? false : true;
        try { await req(`${baseKeys}/${encodeURIComponent(id)}`, { method:'PUT', body: JSON.stringify({ active:newActive }) }); cargar(); } catch(err){ alert('Error toggling'); }
    }
});

// Refrescar
$('#btn-refrescar').addEventListener('click', cargar);

window.addEventListener('load', () => {
    const headerInfo = document.getElementById('header-info');
    if(!ADMIN_API_KEY){
        headerInfo.textContent = 'Falta ADMIN_API_KEY en .env';
        headerInfo.style.color = '#f87171';
    } else {
        headerInfo.textContent = 'Admin key preview: ' + ADMIN_API_KEY.substring(0,12) + '...';
    }
    cargar();
});
</script>
</body>
</html>
