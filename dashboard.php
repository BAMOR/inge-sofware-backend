<?php
// Configuración
$API_BASE     = 'https://inge-sofware-backend.onrender.com';
$API_PERSONAS = 'http://mxx.60c.mytemp.website/projecto/api/persona.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Gestión Tributaria</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#0f1117; color:#e2e8f0; min-height:100vh; }

/* ── Sidebar ── */
.layout { display:flex; min-height:100vh; }
.sidebar {
    width:240px; flex-shrink:0;
    background:#13171f;
    border-right:1px solid #2d3748;
    display:flex; flex-direction:column;
    position:sticky; top:0; height:100vh; overflow-y:auto;
}
.sidebar-logo {
    padding:20px 16px;
    border-bottom:1px solid #2d3748;
    font-weight:800; font-size:1rem;
    background:linear-gradient(135deg,#1e3a5f,#2d6a9f);
}
.sidebar-logo span { font-size:0.7rem; color:#90cdf4; display:block; font-weight:400; margin-top:2px; }
.nav-section { padding:12px 16px 4px; font-size:0.65rem; color:#4a5568; text-transform:uppercase; letter-spacing:.08em; }
.nav-btn {
    display:flex; align-items:center; gap:10px;
    padding:10px 16px; cursor:pointer;
    font-size:0.82rem; color:#a0aec0;
    border:none; background:none; width:100%; text-align:left;
    transition:all .15s;
}
.nav-btn:hover { background:#1a1f2e; color:#fff; }
.nav-btn.active { background:#1a3a5c; color:#90cdf4; border-left:3px solid #4299e1; }
.nav-btn .icon { width:20px; text-align:center; }

/* ── Main ── */
.main { flex:1; padding:24px; overflow-x:hidden; }
.page { display:none; }
.page.active { display:block; }

/* ── Cards ── */
.cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:24px; }
.card {
    background:#1a1f2e; border:1px solid #2d3748;
    border-radius:14px; padding:18px;
    border-top:3px solid;
}
.card.blue { border-top-color:#4299e1; }
.card.green { border-top-color:#48bb78; }
.card.yellow { border-top-color:#ecc94b; }
.card.red { border-top-color:#fc8181; }
.card .val { font-size:1.8rem; font-weight:800; }
.card .lbl { font-size:0.7rem; color:#718096; margin-top:4px; text-transform:uppercase; letter-spacing:.05em; }

/* ── Panel ── */
.panel { background:#1a1f2e; border:1px solid #2d3748; border-radius:14px; padding:20px; margin-bottom:20px; }
.panel h2 { font-size:0.95rem; color:#90cdf4; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid #2d3748; }

/* ── Form ── */
.form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group label { font-size:0.72rem; color:#718096; text-transform:uppercase; letter-spacing:.05em; }
.form-group input, .form-group select {
    background:#0f1117; border:1px solid #2d3748;
    color:#e2e8f0; padding:9px 12px; border-radius:8px;
    font-size:0.85rem; outline:none;
    transition:border .15s;
}
.form-group input:focus, .form-group select:focus { border-color:#4299e1; }

/* ── Botones ── */
.btn {
    padding:9px 20px; border-radius:8px; border:none;
    cursor:pointer; font-size:0.82rem; font-weight:600;
    transition:all .15s; display:inline-flex; align-items:center; gap:6px;
}
.btn-primary { background:#2b6cb0; color:#fff; }
.btn-primary:hover { background:#3182ce; }
.btn-success { background:#276749; color:#fff; }
.btn-success:hover { background:#2f855a; }
.btn-danger { background:#9b2c2c; color:#fff; }
.btn-danger:hover { background:#c53030; }
.btn-warning { background:#7b341e; color:#fff; }
.btn-warning:hover { background:#9c4221; }
.btn-sm { padding:5px 12px; font-size:0.75rem; }
.btn-group { display:flex; gap:8px; margin-top:16px; flex-wrap:wrap; }

/* ── Tabla ── */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:0.8rem; }
th { background:#1e2533; color:#90cdf4; padding:10px 14px; text-align:left; font-size:0.7rem; text-transform:uppercase; letter-spacing:.05em; }
td { padding:10px 14px; border-bottom:1px solid #1e2533; vertical-align:middle; }
tr:hover td { background:#1e2533; }
tr:last-child td { border-bottom:none; }

/* ── Badges ── */
.badge { padding:3px 10px; border-radius:20px; font-size:0.7rem; font-weight:600; }
.badge-pendiente { background:#744210; color:#fbd38d; }
.badge-pagado    { background:#1c4532; color:#9ae6b4; }
.badge-natural   { background:#1a365d; color:#90cdf4; }
.badge-juridico  { background:#322659; color:#d6bcfa; }

/* ── Respuesta ── */
.response-box {
    background:#0a0d14; border:1px solid #2d3748;
    border-radius:8px; padding:14px; margin-top:14px;
    font-family:monospace; font-size:0.78rem;
    max-height:250px; overflow-y:auto; white-space:pre-wrap;
    color:#68d391;
}
.response-box.error { color:#fc8181; }

/* ── Alert ── */
.alert { padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:0.82rem; }
.alert-success { background:#1c4532; border:1px solid #276749; color:#9ae6b4; }
.alert-error   { background:#742a2a; border:1px solid #9b2c2c; color:#feb2b2; }

/* ── Persona card ── */
.persona-card {
    background:#0f1117; border:1px solid #2d3748;
    border-radius:10px; padding:14px;
    margin-bottom:14px;
}
.persona-card .pnombre { font-size:1rem; font-weight:700; color:#90cdf4; }
.persona-card .pdata   { font-size:0.78rem; color:#718096; margin-top:6px; display:grid; grid-template-columns:1fr 1fr; gap:4px; }

/* ── Loading ── */
.loading { display:inline-block; width:16px; height:16px; border:2px solid #4a5568; border-top-color:#4299e1; border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ── Responsive ── */
@media(max-width:700px) {
    .layout { flex-direction:column; }
    .sidebar { width:100%; height:auto; position:relative; }
}
</style>
</head>
<body>
<div class="layout">

<!-- ══════════════ SIDEBAR ══════════════ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        🏛️ Tributaria
        <span>Panel de Administración</span>
    </div>

    <div class="nav-section">Personas (API Externa)</div>
    <button class="nav-btn active" onclick="showPage('personas')">
        <span class="icon">👥</span> Ver Personas
    </button>

    <div class="nav-section">Contribuyentes</div>
    <button class="nav-btn" onclick="showPage('contrib-list')">
        <span class="icon">📋</span> Ver todos
    </button>
    <button class="nav-btn" onclick="showPage('contrib-crear')">
        <span class="icon">➕</span> Registrar
    </button>
    <button class="nav-btn" onclick="showPage('contrib-editar')">
        <span class="icon">✏️</span> Actualizar
    </button>
    <button class="nav-btn" onclick="showPage('contrib-eliminar')">
        <span class="icon">🗑️</span> Eliminar
    </button>

    <div class="nav-section">Declaraciones</div>
    <button class="nav-btn" onclick="showPage('decl-list')">
        <span class="icon">📄</span> Ver todas
    </button>
    <button class="nav-btn" onclick="showPage('decl-crear')">
        <span class="icon">➕</span> Nueva declaración
    </button>
    <button class="nav-btn" onclick="showPage('decl-pagar')">
        <span class="icon">💳</span> Marcar pagado
    </button>

    <div class="nav-section">Resumen</div>
    <button class="nav-btn" onclick="showPage('stats')">
        <span class="icon">📊</span> Estadísticas
    </button>
</aside>

<!-- ══════════════ MAIN ══════════════ -->
<main class="main">

<!-- ─── PERSONAS ─────────────────── -->
<div id="page-personas" class="page active">
    <div class="panel">
        <h2>👥 Personas — API Externa</h2>
        <p style="font-size:.78rem;color:#718096;margin-bottom:14px">
            Fuente: <code style="color:#90cdf4"><?= $API_PERSONAS ?></code>
        </p>
        <div class="btn-group" style="margin-top:0;margin-bottom:14px">
            <button class="btn btn-primary" onclick="getPersonas()">🔄 Cargar todas las personas</button>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:14px">
            <div class="form-group" style="flex:1">
                <label>Buscar por ID persona</label>
                <input type="number" id="buscar-persona-id" placeholder="Ej: 1">
            </div>
            <div style="display:flex;align-items:flex-end">
                <button class="btn btn-primary" onclick="getPersonaById()">🔍 Buscar</button>
            </div>
        </div>
        <div id="res-personas"></div>
    </div>
</div>

<!-- ─── CONTRIBUYENTES LIST ─────── -->
<div id="page-contrib-list" class="page">
    <div class="panel">
        <h2>📋 Todos los Contribuyentes</h2>
        <div class="btn-group" style="margin-top:0;margin-bottom:14px">
            <button class="btn btn-primary" onclick="getContribuyentes()">🔄 Cargar contribuyentes</button>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap">
            <div class="form-group" style="flex:1;min-width:160px">
                <label>Buscar por ID persona</label>
                <input type="number" id="buscar-contrib-persona" placeholder="Ej: 1">
            </div>
            <div class="form-group" style="flex:1;min-width:160px">
                <label>Buscar por ID contribuyente</label>
                <input type="number" id="buscar-contrib-id" placeholder="Ej: 1">
            </div>
            <div style="display:flex;align-items:flex-end;gap:8px">
                <button class="btn btn-primary" onclick="getContribByPersona()">🔍 Por persona</button>
                <button class="btn btn-primary" onclick="getContribById()">🔍 Por ID</button>
            </div>
        </div>
        <div id="res-contrib-list"></div>
    </div>
</div>

<!-- ─── CREAR CONTRIBUYENTE ──────── -->
<div id="page-contrib-crear" class="page">
    <div class="panel">
        <h2>➕ Registrar Contribuyente</h2>
        <p style="font-size:.78rem;color:#718096;margin-bottom:16px">
            Verifica que la persona exista en la API externa antes de registrar.
        </p>
        <div class="form-grid">
            <div class="form-group">
                <label>ID Persona *</label>
                <input type="number" id="cc-id-persona" placeholder="Ej: 1">
            </div>
            <div class="form-group">
                <label>NIT *</label>
                <input type="text" id="cc-nit" placeholder="Ej: 1234567-8">
            </div>
            <div class="form-group">
                <label>Tipo Contribuyente</label>
                <select id="cc-tipo">
                    <option value="Natural">Natural</option>
                    <option value="Jurídico">Jurídico</option>
                </select>
            </div>
            <div class="form-group">
                <label>Régimen Fiscal</label>
                <select id="cc-regimen">
                    <option value="Pequeño Contribuyente">Pequeño Contribuyente (5%)</option>
                    <option value="General">General (12%)</option>
                    <option value="Opcional Simplificado">Opcional Simplificado (7%)</option>
                </select>
            </div>
        </div>
        <div class="btn-group">
            <button class="btn btn-success" onclick="crearContribuyente()">✅ Registrar Contribuyente</button>
        </div>
        <div id="res-contrib-crear"></div>
    </div>
</div>

<!-- ─── EDITAR CONTRIBUYENTE ─────── -->
<div id="page-contrib-editar" class="page">
    <div class="panel">
        <h2>✏️ Actualizar Contribuyente</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>ID Contribuyente *</label>
                <input type="number" id="ce-id" placeholder="Ej: 1">
            </div>
            <div class="form-group">
                <label>Nuevo NIT</label>
                <input type="text" id="ce-nit" placeholder="Dejar vacío para no cambiar">
            </div>
            <div class="form-group">
                <label>Tipo Contribuyente</label>
                <select id="ce-tipo">
                    <option value="">-- No cambiar --</option>
                    <option value="Natural">Natural</option>
                    <option value="Jurídico">Jurídico</option>
                </select>
            </div>
            <div class="form-group">
                <label>Régimen Fiscal</label>
                <select id="ce-regimen">
                    <option value="">-- No cambiar --</option>
                    <option value="Pequeño Contribuyente">Pequeño Contribuyente (5%)</option>
                    <option value="General">General (12%)</option>
                    <option value="Opcional Simplificado">Opcional Simplificado (7%)</option>
                </select>
            </div>
        </div>
        <div class="btn-group">
            <button class="btn btn-warning" onclick="actualizarContribuyente()">✏️ Actualizar</button>
        </div>
        <div id="res-contrib-editar"></div>
    </div>
</div>

<!-- ─── ELIMINAR CONTRIBUYENTE ────── -->
<div id="page-contrib-eliminar" class="page">
    <div class="panel">
        <h2>🗑️ Eliminar Contribuyente</h2>
        <p style="font-size:.78rem;color:#fc8181;margin-bottom:16px">
            ⚠️ La eliminación es lógica (activo = 0), no se borran los datos.
        </p>
        <div class="form-group" style="max-width:300px">
            <label>ID Contribuyente *</label>
            <input type="number" id="del-id" placeholder="Ej: 1">
        </div>
        <div class="btn-group">
            <button class="btn btn-danger" onclick="eliminarContribuyente()">🗑️ Eliminar</button>
        </div>
        <div id="res-contrib-eliminar"></div>
    </div>
</div>

<!-- ─── DECLARACIONES LIST ────────── -->
<div id="page-decl-list" class="page">
    <div class="panel">
        <h2>📄 Declaraciones Tributarias</h2>
        <div class="btn-group" style="margin-top:0;margin-bottom:14px">
            <button class="btn btn-primary" onclick="getDeclaraciones()">🔄 Cargar todas</button>
        </div>
        <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap">
            <div class="form-group" style="flex:1;min-width:160px">
                <label>Por ID Contribuyente</label>
                <input type="number" id="buscar-decl-contrib" placeholder="Ej: 1">
            </div>
            <div class="form-group" style="flex:1;min-width:120px">
                <label>Período (opcional)</label>
                <input type="text" id="buscar-decl-periodo" placeholder="Ej: 2024-01">
            </div>
            <div style="display:flex;align-items:flex-end">
                <button class="btn btn-primary" onclick="getDeclByContrib()">🔍 Buscar</button>
            </div>
        </div>
        <div id="res-decl-list"></div>
    </div>
</div>

<!-- ─── NUEVA DECLARACIÓN ─────────── -->
<div id="page-decl-crear" class="page">
    <div class="panel">
        <h2>➕ Nueva Declaración — Cálculo Automático de Impuesto</h2>
        <div style="background:#0f1117;border:1px solid #2d3748;border-radius:8px;padding:12px;margin-bottom:16px;font-size:.78rem;color:#90cdf4">
            📐 Fórmula: <strong>base = ingresos - egresos</strong> | <strong>impuesto = base × tasa%</strong>
            <br>Tasas: Pequeño Contribuyente = 5% | General = 12% | Opcional Simplificado = 7%
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>ID Contribuyente *</label>
                <input type="number" id="dc-id-contrib" placeholder="Ej: 1">
            </div>
            <div class="form-group">
                <label>Período *</label>
                <input type="text" id="dc-periodo" placeholder="Ej: 2024-01">
            </div>
            <div class="form-group">
                <label>Ingresos (Q) *</label>
                <input type="number" id="dc-ingresos" placeholder="Ej: 50000" step="0.01">
            </div>
            <div class="form-group">
                <label>Egresos (Q) *</label>
                <input type="number" id="dc-egresos" placeholder="Ej: 20000" step="0.01">
            </div>
        </div>
        <div class="btn-group">
            <button class="btn btn-success" onclick="crearDeclaracion()">🧮 Calcular y Registrar</button>
        </div>
        <div id="res-decl-crear"></div>
    </div>
</div>

<!-- ─── MARCAR PAGADO ─────────────── -->
<div id="page-decl-pagar" class="page">
    <div class="panel">
        <h2>💳 Actualizar Declaración / Marcar como Pagado</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>ID Declaración *</label>
                <input type="number" id="dp-id" placeholder="Ej: 1">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select id="dp-estado">
                    <option value="Pagado">Pagado</option>
                    <option value="Pendiente">Pendiente</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nuevos Ingresos (opcional)</label>
                <input type="number" id="dp-ingresos" placeholder="Dejar vacío para no cambiar" step="0.01">
            </div>
            <div class="form-group">
                <label>Nuevos Egresos (opcional)</label>
                <input type="number" id="dp-egresos" placeholder="Dejar vacío para no cambiar" step="0.01">
            </div>
        </div>
        <div class="btn-group">
            <button class="btn btn-success" onclick="actualizarDeclaracion()">💳 Actualizar Declaración</button>
        </div>
        <div id="res-decl-pagar"></div>
    </div>
</div>

<!-- ─── ESTADÍSTICAS ─────────────── -->
<div id="page-stats" class="page">
    <div class="panel">
        <h2>📊 Estadísticas Generales</h2>
        <div class="btn-group" style="margin-top:0;margin-bottom:14px">
            <button class="btn btn-primary" onclick="cargarStats()">🔄 Cargar estadísticas</button>
        </div>
        <div id="res-stats"></div>
    </div>
</div>

</main>
</div>

<script>
const API   = '<?= $API_BASE ?>';
const PERS  = '<?= $API_PERSONAS ?>';

// ── Navegación ──────────────────────────────────────────
function showPage(id) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('page-' + id).classList.add('active');
    event.currentTarget.classList.add('active');
}

// ── Helpers ─────────────────────────────────────────────
function showJSON(elId, data, isError = false) {
    const el = document.getElementById(elId);
    el.innerHTML = '<div class="response-box' + (isError ? ' error' : '') + '">' +
        JSON.stringify(data, null, 2) + '</div>';
}

function showTable(elId, rows, cols) {
    if (!rows || rows.length === 0) {
        document.getElementById(elId).innerHTML = '<p style="color:#718096;padding:10px">Sin resultados</p>';
        return;
    }
    let html = '<div class="table-wrap"><table><thead><tr>';
    cols.forEach(c => html += '<th>' + c.label + '</th>');
    html += '</tr></thead><tbody>';
    rows.forEach(r => {
        html += '<tr>';
        cols.forEach(c => {
            let val = r[c.key] !== null && r[c.key] !== undefined ? r[c.key] : '—';
            if (c.badge) {
                const cls = val === 'Pagado' ? 'badge-pagado' : 'badge-pendiente';
                val = '<span class="badge ' + cls + '">' + val + '</span>';
            }
            if (c.money) val = 'Q' + parseFloat(val || 0).toFixed(2);
            html += '<td>' + val + '</td>';
        });
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById(elId).innerHTML = html;
}

function loading(elId) {
    document.getElementById(elId).innerHTML = '<div style="padding:10px;color:#718096"><span class="loading"></span> Procesando...</div>';
}

async function apiFetch(url, method = 'GET', body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    return await res.json();
}

// ══════════════════════════════════════════════
// PERSONAS
// ══════════════════════════════════════════════
async function getPersonas() {
    loading('res-personas');
    try {
        const data = await apiFetch(PERS);
        const arr  = Array.isArray(data) ? data : [data];
        if (arr.length === 0) {
            document.getElementById('res-personas').innerHTML = '<div class="alert alert-error">La API de personas no tiene datos aún.</div>';
            return;
        }
        let html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">';
        arr.forEach(p => {
            const nombre = [p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido].filter(Boolean).join(' ');
            html += `<div class="persona-card">
                <div class="pnombre">👤 ${nombre || 'Sin nombre'}</div>
                <div class="pdata">
                    <span>🪪 DPI: ${p.numero_documento || '—'}</span>
                    <span>📋 Tipo: ${p.tipo_documento || '—'}</span>
                    <span>📍 ${p.municipio || '—'}</span>
                    <span>💼 ${p.profesion || '—'}</span>
                    <span>✉️ ${p.correo || '—'}</span>
                    <span>🎂 ${p.fecha_nacimiento || '—'}</span>
                    <span style="color:#90cdf4">🔑 ID: ${p.id_persona}</span>
                </div>
            </div>`;
        });
        html += '</div>';
        document.getElementById('res-personas').innerHTML = html;
    } catch(e) {
        showJSON('res-personas', { error: 'No se pudo conectar a la API de personas' }, true);
    }
}

async function getPersonaById() {
    const id = document.getElementById('buscar-persona-id').value;
    if (!id) { alert('Ingresa un ID'); return; }
    loading('res-personas');
    try {
        const data = await apiFetch(PERS + '?id_persona=' + id);
        const arr  = Array.isArray(data) ? data : [data];
        const p    = arr[0];
        if (!p || p.error) { showJSON('res-personas', { error: 'Persona no encontrada' }, true); return; }
        const nombre = [p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido].filter(Boolean).join(' ');
        document.getElementById('res-personas').innerHTML = `
            <div class="persona-card">
                <div class="pnombre">👤 ${nombre || 'Sin nombre'}</div>
                <div class="pdata">
                    <span>🪪 DPI: ${p.numero_documento || '—'}</span>
                    <span>📋 Tipo: ${p.tipo_documento || '—'}</span>
                    <span>📍 ${p.municipio || '—'}</span>
                    <span>💼 ${p.profesion || '—'}</span>
                    <span>✉️ ${p.correo || '—'}</span>
                    <span>🎂 ${p.fecha_nacimiento || '—'}</span>
                    <span>⚥ ${p.genero || '—'}</span>
                    <span>💍 ${p.estado_civil || '—'}</span>
                    <span style="color:#90cdf4">🔑 ID: ${p.id_persona}</span>
                </div>
            </div>`;
    } catch(e) {
        showJSON('res-personas', { error: 'Error de conexión' }, true);
    }
}

// ══════════════════════════════════════════════
// CONTRIBUYENTES
// ══════════════════════════════════════════════
async function getContribuyentes() {
    loading('res-contrib-list');
    const data = await apiFetch(API + '/tributaria.php');
    showTable('res-contrib-list', Array.isArray(data) ? data : [data], [
        {key:'id_contribuyente', label:'ID'},
        {key:'nombre_completo',  label:'Nombre'},
        {key:'nit',              label:'NIT'},
        {key:'tipo_contribuyente',label:'Tipo'},
        {key:'regimen',          label:'Régimen'},
        {key:'fecha_registro',   label:'Registro'},
    ]);
}

async function getContribByPersona() {
    const id = document.getElementById('buscar-contrib-persona').value;
    if (!id) { alert('Ingresa ID de persona'); return; }
    loading('res-contrib-list');
    const data = await apiFetch(API + '/tributaria.php?id_persona=' + id);
    showJSON('res-contrib-list', data, !!data.error);
}

async function getContribById() {
    const id = document.getElementById('buscar-contrib-id').value;
    if (!id) { alert('Ingresa ID de contribuyente'); return; }
    loading('res-contrib-list');
    const data = await apiFetch(API + '/tributaria.php?id_contribuyente=' + id);
    showJSON('res-contrib-list', data, !!data.error);
}

async function crearContribuyente() {
    const id_persona = document.getElementById('cc-id-persona').value;
    const nit        = document.getElementById('cc-nit').value;
    if (!id_persona || !nit) { alert('ID Persona y NIT son obligatorios'); return; }
    loading('res-contrib-crear');
    const data = await apiFetch(API + '/tributaria.php', 'POST', {
        id_persona: parseInt(id_persona),
        nit,
        tipo_contribuyente: document.getElementById('cc-tipo').value,
        regimen:            document.getElementById('cc-regimen').value,
    });
    showJSON('res-contrib-crear', data, !!data.error);
}

async function actualizarContribuyente() {
    const id = document.getElementById('ce-id').value;
    if (!id) { alert('ID Contribuyente obligatorio'); return; }
    const body = { id_contribuyente: parseInt(id) };
    const nit     = document.getElementById('ce-nit').value;
    const tipo    = document.getElementById('ce-tipo').value;
    const regimen = document.getElementById('ce-regimen').value;
    if (nit)     body.nit              = nit;
    if (tipo)    body.tipo_contribuyente = tipo;
    if (regimen) body.regimen          = regimen;
    loading('res-contrib-editar');
    const data = await apiFetch(API + '/tributaria.php', 'PUT', body);
    showJSON('res-contrib-editar', data, !!data.error);
}

async function eliminarContribuyente() {
    const id = document.getElementById('del-id').value;
    if (!id) { alert('ID Contribuyente obligatorio'); return; }
    if (!confirm('¿Eliminar contribuyente #' + id + '?')) return;
    loading('res-contrib-eliminar');
    const data = await apiFetch(API + '/tributaria.php', 'DELETE', { id_contribuyente: parseInt(id) });
    showJSON('res-contrib-eliminar', data, !!data.error);
}

// ══════════════════════════════════════════════
// DECLARACIONES
// ══════════════════════════════════════════════
async function getDeclaraciones() {
    loading('res-decl-list');
    const data = await apiFetch(API + '/declaracion.php');
    showTable('res-decl-list', Array.isArray(data) ? data : [data], [
        {key:'id_declaracion',     label:'ID'},
        {key:'nit',                label:'NIT'},
        {key:'periodo',            label:'Período'},
        {key:'ingresos',           label:'Ingresos',   money:true},
        {key:'egresos',            label:'Egresos',    money:true},
        {key:'base_imponible',     label:'Base',       money:true},
        {key:'tasa_impuesto',      label:'Tasa'},
        {key:'impuesto_calculado', label:'Impuesto',   money:true},
        {key:'estado',             label:'Estado',     badge:true},
    ]);
}

async function getDeclByContrib() {
    const id      = document.getElementById('buscar-decl-contrib').value;
    const periodo = document.getElementById('buscar-decl-periodo').value;
    if (!id) { alert('Ingresa ID de contribuyente'); return; }
    loading('res-decl-list');
    let url = API + '/declaracion.php?id_contribuyente=' + id;
    if (periodo) url += '&periodo=' + periodo;
    const data = await apiFetch(url);
    showJSON('res-decl-list', data, !!data.error);
}

async function crearDeclaracion() {
    const id_contrib = document.getElementById('dc-id-contrib').value;
    const periodo    = document.getElementById('dc-periodo').value;
    const ingresos   = document.getElementById('dc-ingresos').value;
    const egresos    = document.getElementById('dc-egresos').value;
    if (!id_contrib || !periodo || ingresos === '' || egresos === '') {
        alert('Todos los campos son obligatorios'); return;
    }
    loading('res-decl-crear');
    const data = await apiFetch(API + '/declaracion.php', 'POST', {
        id_contribuyente: parseInt(id_contrib),
        periodo,
        ingresos:  parseFloat(ingresos),
        egresos:   parseFloat(egresos),
    });
    if (!data.error) {
        document.getElementById('res-decl-crear').innerHTML =
            `<div class="alert alert-success">
                ✅ Declaración creada<br>
                📐 Base imponible: <strong>Q${data.base_imponible}</strong><br>
                📊 Tasa: <strong>${data.tasa_impuesto}</strong><br>
                💰 Impuesto calculado: <strong>Q${data.impuesto_calculado}</strong>
            </div>` +
            `<div class="response-box">${JSON.stringify(data, null, 2)}</div>`;
    } else {
        showJSON('res-decl-crear', data, true);
    }
}

async function actualizarDeclaracion() {
    const id = document.getElementById('dp-id').value;
    if (!id) { alert('ID Declaración obligatorio'); return; }
    const body = {
        id_declaracion: parseInt(id),
        estado: document.getElementById('dp-estado').value,
    };
    const ingresos = document.getElementById('dp-ingresos').value;
    const egresos  = document.getElementById('dp-egresos').value;
    if (ingresos) body.ingresos = parseFloat(ingresos);
    if (egresos)  body.egresos  = parseFloat(egresos);
    loading('res-decl-pagar');
    const data = await apiFetch(API + '/declaracion.php', 'PUT', body);
    showJSON('res-decl-pagar', data, !!data.error);
}

// ══════════════════════════════════════════════
// ESTADÍSTICAS
// ══════════════════════════════════════════════
async function cargarStats() {
    loading('res-stats');
    const [contribs, decls] = await Promise.all([
        apiFetch(API + '/tributaria.php'),
        apiFetch(API + '/declaracion.php'),
    ]);

    const ca  = Array.isArray(contribs) ? contribs : [];
    const da  = Array.isArray(decls)    ? decls    : [];

    const pendientes = da.filter(d => d.estado === 'Pendiente');
    const pagados    = da.filter(d => d.estado === 'Pagado');
    const totalPend  = pendientes.reduce((s, d) => s + parseFloat(d.impuesto_calculado||0), 0);
    const totalPag   = pagados.reduce((s, d)    => s + parseFloat(d.impuesto_calculado||0), 0);

    document.getElementById('res-stats').innerHTML = `
        <div class="cards">
            <div class="card blue">
                <div class="card-icon">👥</div>
                <div class="val">${ca.length}</div>
                <div class="lbl">Contribuyentes</div>
            </div>
            <div class="card green">
                <div class="card-icon">📋</div>
                <div class="val">${da.length}</div>
                <div class="lbl">Declaraciones</div>
            </div>
            <div class="card yellow">
                <div class="card-icon">⏳</div>
                <div class="val">Q${totalPend.toFixed(2)}</div>
                <div class="lbl">Pendiente de cobro</div>
            </div>
            <div class="card green">
                <div class="card-icon">✅</div>
                <div class="val">Q${totalPag.toFixed(2)}</div>
                <div class="lbl">Total cobrado</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:4px">
            <div class="panel" style="margin:0">
                <h2>📊 Por Estado</h2>
                <div style="display:flex;flex-direction:column;gap:10px">
                    ${[['Pendiente', pendientes.length, '#ecc94b'], ['Pagado', pagados.length, '#48bb78']]
                      .map(([e,n,c]) => `
                        <div>
                            <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:4px">
                                <span>${e}</span><span>${n}</span>
                            </div>
                            <div style="background:#2d3748;border-radius:6px;height:10px;overflow:hidden">
                                <div style="background:${c};height:100%;width:${da.length ? (n/da.length*100) : 0}%;border-radius:6px"></div>
                            </div>
                        </div>`).join('')}
                </div>
            </div>
            <div class="panel" style="margin:0">
                <h2>💰 Impuesto Total</h2>
                <div style="font-size:1.6rem;font-weight:800;color:#68d391">
                    Q${(totalPend + totalPag).toFixed(2)}
                </div>
                <div style="font-size:.75rem;color:#718096;margin-top:6px">
                    Suma de todos los impuestos calculados
                </div>
            </div>
        </div>`;
}
</script>
</body>
</html>