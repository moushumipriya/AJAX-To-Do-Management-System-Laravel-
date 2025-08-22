<!DOCTYPE html>
<html lang="en" data-theme="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AJAX To-Do</title>

    {{-- CSRF for AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Breeze/Vite assets --}}
    @vite(['resources/css/app.css','resources/js/app.js'])

    {{-- Minimal Google-Form style --}}
    <style>
        body { display:flex; align-items:flex-start; justify-content:center; min-height:100vh; padding:2rem; }
        .card { width: 100%; max-width: 840px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,.08); padding: 1.25rem; background: var(--card-bg,#fff);}
        .row { display:flex; gap:1rem; }
        .field { display:flex; flex-direction:column; gap:.5rem; flex:1; }
        input, textarea, select, button { border-radius:12px; padding:.8rem 1rem; border:1px solid #ddd; }
        table { width:100%; border-collapse:separate; border-spacing:0 8px; }
        td, th { padding:.75rem 1rem; background: var(--row-bg,#fafafa); }
        tr:hover td { filter: brightness(0.98); }
        .actions { display:flex; gap:.5rem; }
        .pill { display:inline-block; padding:.25rem .6rem; border-radius:999px; font-size:.85rem; }
        .success { background:#e8f5e9; }
        .error { background:#ffebee; }
        .hidden { display:none; }

        /* Dark mode */
        .dark { --card-bg:#0f172a; --row-bg:#111827; color:#e5e7eb; }
        .dark input, .dark textarea, .dark select { background:#0b1220; color:#e5e7eb; border-color:#1f2937; }
        .dark td, .dark th { background:#0b1220; }
        .dark .pill { background:#1f2937; }
    </style>
</head>
<body>
    <div class="card" id="appCard">
        <div class="row" style="justify-content:space-between; align-items:center;">
            <h2 style="margin:.5rem 0;">To-Do Management (AJAX)</h2>

            {{-- Theme Toggle (Cookie) --}}
            <div class="row" style="align-items:center;">
                <label for="themeToggle">Theme:</label>
                <select id="themeToggle">
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </select>
            </div>
        </div>

        {{-- Alerts --}}
        <div id="alertBox" class="hidden"></div>

        {{-- Create / Edit Form --}}
        <form id="taskForm" class="row" style="margin:1rem 0 1.25rem 0;">
            <div class="field">
                <label>Title <span style="color:#ef4444">*</span></label>
                <input type="text" name="title" id="title" placeholder="e.g., Buy milk">
                <small id="err_title" class="error hidden pill"></small>
            </div>

            <div class="field">
                <label>Description</label>
                <input type="text" name="description" id="description" placeholder="(optional, max 255)">
                <small id="err_description" class="error hidden pill"></small>
            </div>

            <div class="field" style="max-width:200px;">
                <label>Status</label>
                <select name="status" id="status">
                    <option value="Pending" selected>Pending</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>

            <div class="field" style="max-width:160px; justify-content:flex-end;">
                <input type="hidden" id="edit_id" value="">
                <button type="submit" id="saveBtn">Save</button>
            </div>
        </form>

        {{-- Task List --}}
        <table>
            <thead>
                <tr>
                    <th style="width:8rem;">Status</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th style="width:11rem;">Actions</th>
                </tr>
            </thead>
            <tbody id="taskBody">
                {{-- Rows will be injected --}}
            </tbody>
        </table>
    </div>

<script>
/* =========================
   CSRF Setup for Fetch
========================= */
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const jsonHeaders = {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': CSRF_TOKEN,
    'Accept': 'application/json'
};

/* =========================
   Theme Cookie (Light/Dark)
========================= */
const themeSelect = document.getElementById('themeToggle');
const root = document.querySelector('html');

function setCookie(name, value, days=365) {
    const expires = new Date(Date.now() + days*864e5).toUTCString();
    document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax';
}
function getCookie(name) {
    return document.cookie.split('; ').find(row => row.startsWith(name + '='))?.split('=')[1];
}
function applyTheme(theme) {
    if (theme === 'dark') {
        document.body.classList.add('dark');
        themeSelect.value = 'dark';
    } else {
        document.body.classList.remove('dark');
        themeSelect.value = 'light';
    }
}

const savedTheme = decodeURIComponent(getCookie('theme') || 'light');
applyTheme(savedTheme);
themeSelect.addEventListener('change', e => {
    const val = e.target.value;
    setCookie('theme', val);
    applyTheme(val);
});

/* =========================
   Alerts Helper
========================= */
const alertBox = document.getElementById('alertBox');
function showAlert(msg, type='success') {
    alertBox.classList.remove('hidden');
    alertBox.classList.toggle('success', type==='success');
    alertBox.classList.toggle('error', type==='error');
    alertBox.textContent = msg;
    alertBox.classList.add('pill');
    setTimeout(() => alertBox.classList.add('hidden'), 2500);
}

/* =========================
   Load & Render Tasks
========================= */
const bodyEl = document.getElementById('taskBody');

async function loadTasks() {
    const res = await fetch(`{{ route('tasks.list') }}`, { headers: { 'Accept': 'application/json' }});
    const data = await res.json();
    renderRows(data);
}

function renderRows(tasks) {
    bodyEl.innerHTML = '';
    tasks.forEach(t => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <span class="pill">${t.status}</span>
            </td>
            <td>${escapeHtml(t.title)}</td>
            <td>${escapeHtml(t.description ?? '')}</td>
            <td class="actions">
                <button data-id="${t.id}" data-action="edit">Edit</button>
                <button data-id="${t.id}" data-action="delete">Delete</button>
            </td>
        `;
        bodyEl.appendChild(tr);
    });
}

function escapeHtml(str) {
    return (str ?? '').toString()
        .replaceAll('&','&amp;').replaceAll('<','&lt;')
        .replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;");
}

/* =========================
   Create / Update Submit
========================= */
const form = document.getElementById('taskForm');
const titleEl = document.getElementById('title');
const descEl  = document.getElementById('description');
const statusEl= document.getElementById('status');
const editIdEl= document.getElementById('edit_id');

const errTitle = document.getElementById('err_title');
const errDesc  = document.getElementById('err_description');

function clearErrors() {
    [errTitle, errDesc].forEach(e => { e.textContent=''; e.classList.add('hidden'); });
}

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();

    const payload = {
        title: titleEl.value.trim(),
        description: descEl.value.trim() || null,
        status: statusEl.value
    };

    const isEdit = !!editIdEl.value;
    const url = isEdit
        ? `{{ url('/tasks') }}/${editIdEl.value}`
        : `{{ route('tasks.store') }}`;

    const res = await fetch(url, {
        method: isEdit ? 'PUT' : 'POST',
        headers: jsonHeaders,
        body: JSON.stringify(payload)
    });

    if (res.ok) {
        const json = await res.json();
        showAlert(json.message || (isEdit ? 'Updated' : 'Created'), 'success');
        form.reset();
        statusEl.value = 'Pending';
        editIdEl.value = '';
        await loadTasks();
    } else if (res.status === 422) {
        const { errors } = await res.json();
        if (errors?.title) { errTitle.textContent = errors.title[0]; errTitle.classList.remove('hidden'); }
        if (errors?.description) { errDesc.textContent = errors.description[0]; errDesc.classList.remove('hidden'); }
        showAlert('Validation failed.', 'error');
    } else {
        showAlert('Something went wrong.', 'error');
    }
});

/* =========================
   Edit/Delete Actions
========================= */
bodyEl.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    const action = btn.getAttribute('data-action');

    if (action === 'edit') {
        // Inline edit: form এ ভ্যালু বসান
        const row = btn.closest('tr');
        const t = row.children;
        editIdEl.value = id;
        titleEl.value = t[1].textContent.trim();
        descEl.value  = t[2].textContent.trim();
        statusEl.value = t[0].textContent.trim().includes('Completed') ? 'Completed' : 'Pending';
        showAlert('Editing mode enabled');
    }

    if (action === 'delete') {
        if (!confirm('Are you sure you want to delete this task?')) return;

        const res = await fetch(`{{ url('/tasks') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        });

        if (res.ok) {
            const json = await res.json();
            showAlert(json.message || 'Deleted', 'success');
            await loadTasks();
        } else {
            showAlert('Delete failed.', 'error');
        }
    }
});

/* =========================
   Initial Load
========================= */
loadTasks();

</script>
</body>
</html>
