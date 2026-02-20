<?php
/**
 * Member Dashboard - Media View
 * 
 * Shows file library isolated to member folder.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper to determine if we act as a standalone page (which we do in this controller setup)
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meine Dateien - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    
    <!-- Core Styles -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    
    <!-- Sidebar Styles -->
    <?php if (function_exists('renderMemberSidebarStyles')) renderMemberSidebarStyles(); ?>

    <style>
        /* Specific Media Styles */
        .media-toolbar { display:flex; justify-content:space-between; margin:20px 0; background:#fff; padding:15px; border-radius:8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); flex-wrap: wrap; gap: 10px; }
        .breadcrumb span { cursor:pointer; color:#667eea; font-weight: 500; }
        .breadcrumb span:hover { text-decoration: underline; }
        
        .media-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        
        .media-table { width:100%; border-collapse:collapse; }
        .media-table th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 15px; text-align: left; }
        .media-table td { padding:12px 15px; text-align:left; border-bottom:1px solid #f1f5f9; color: #334155; }
        .media-table tr:last-child td { border-bottom: none; }
        .media-table tr:hover td { background: #f8fafc; }
        
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; }
        
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index: 1000; }
        .modal.active { display:flex; }
        .modal-content { background:#fff; padding:25px; border-radius:12px; width:400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); max-width: 90%; }
        .modal h3 { margin-top: 0; color: #1e293b; }
        .modal-footer { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
        
        .btn { padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a67d8; }
        .btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
        .btn-outline:hover { background: #f1f5f9; border-color: #94a3b8; }
        .btn-danger { background: #fee2e2; color: #ef4444; }
        .btn-danger:hover { background: #fecaca; }

        .search-box input { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; width: 250px; max-width: 100%; }
        
        /* Ensure compatibility with member layout */
        body { margin: 0; background: #f7fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
    </style>
</head>
<body class="member-body">

<?php 
// Render sidebar
if (defined('CORE_PATH') && function_exists('renderMemberSidebar')) {
    renderMemberSidebar('media');
}
?>

<div class="member-content">

    <?php if (!empty($settings['member_uploads_enabled']) && !$settings['member_uploads_enabled'] && !Auth::isAdmin()): ?>
        <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffeeba;">
            <strong>Hinweis:</strong> Die Medienverwaltung ist derzeit deaktiviert.
        </div>
    <?php else: ?>

    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin:0; font-size: 1.75rem; color: #1a202c;">Meine Dateien</h1>
            <p style="margin:5px 0 0; color: #718096;">Verwalten Sie hier Ihre persönlichen Dokumente und Medien.</p>
        </div>
        <div class="header-actions" style="display: flex; gap: 10px;">
            <button class="btn btn-outline" onclick="openCreateFolderModal()">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                Neuer Ordner
            </button>
            <button class="btn btn-primary" onclick="document.getElementById('member-upload').click()">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                Datei hochladen
            </button>
            <input type="file" id="member-upload" multiple style="display:none;" onchange="handleMemberUpload(this)">
        </div>
    </div>

    <div class="media-toolbar">
        <div class="breadcrumb" id="breadcrumb" style="display: flex; align-items: center; gap: 8px;">
            <span onclick="loadPath('')">Home</span>
        </div>
        <div class="search-box">
            <input type="text" placeholder="Suchen..." id="search-input" onkeyup="applyFilter()">
        </div>
    </div>

    <div class="media-card">
        <div style="overflow-x: auto;">
            <table class="media-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Icon</th>
                        <th>Name</th>
                        <th>Typ</th>
                        <th>Größe</th>
                        <th style="text-align: right;">Aktionen</th>
                    </tr>
                </thead>
                <tbody id="file-list">
                    <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">Lade Dateien...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

</div>

<!-- Modals -->
<div class="modal" id="create-folder-modal">
    <div class="modal-content">
        <h3>Neuer Ordner</h3>
        <input type="text" id="new-folder-name" class="form-control" style="width:100%; padding:10px; box-sizing:border-box; border:1px solid #cbd5e1; border-radius:6px; margin: 10px 0;" placeholder="Name" onkeydown="if(event.key === 'Enter') createFolder()">
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('create-folder-modal')">Abbrechen</button>
            <button class="btn btn-primary" onclick="createFolder()">Erstellen</button>
        </div>
    </div>
</div>

<div class="modal" id="rename-modal">
    <div class="modal-content">
        <h3>Umbenennen</h3>
        <input type="hidden" id="rename-old-path">
        <input type="text" id="rename-new-name" class="form-control" style="width:100%; padding:10px; box-sizing:border-box; border:1px solid #cbd5e1; border-radius:6px; margin: 10px 0;" onkeydown="if(event.key === 'Enter') renameItem()">
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('rename-modal')">Abbrechen</button>
            <button class="btn btn-primary" onclick="renameItem()">Speichern</button>
        </div>
    </div>
</div>

<script>
// Use a dedicated handler file to bypass CMS routing issues for AJAX
// Strategy: Construct path relative to the current URL base (likely /member/)
let baseUrl = window.location.href.split('?')[0];

// Handle trailing slash or filename
if (!baseUrl.endsWith('/')) {
    baseUrl = baseUrl.endsWith('.php') 
        ? baseUrl.substring(0, baseUrl.lastIndexOf('/') + 1)
        : baseUrl.substring(0, baseUrl.lastIndexOf('/') + 1);
}

let proxyUrl = new URL('../media-proxy.php', baseUrl).toString();

// Verify we didn't go too far up if baseUrl was root (unlikely for member area)
const AJAX_URL = proxyUrl;
console.log('Using Root Proxy AJAX URL:', AJAX_URL);

// REMOVE DUPLICATE DECLARATION
// let currentPath = ''; <--- This was the error
if (typeof currentPath === 'undefined') {
    var currentPath = ''; // Use var or let scope carefully
} else {
    currentPath = '';
}

// Add error handler for network issues
window.addEventListener('unhandledrejection', function(event) {
  console.error('Unhandled promise rejection:', event.reason);
});

document.addEventListener('DOMContentLoaded', () => {
    loadPath('');
});

async function loadPath(path) {
    currentPath = path;
    updateBreadcrumb(path);
    const tbody = document.getElementById('file-list');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">Lade...</td></tr>';
    
    const fd = new FormData();
    fd.append('action', 'list_files');
    fd.append('path', path);
    
    try {
        const resp = await fetch(AJAX_URL, {method:'POST', body:fd});
        if (!resp.ok) throw new Error('HTTP Error ' + resp.status);
        
        let res;
        try {
            res = await resp.json();
        } catch(e) {
            console.error('JSON Parse Error', e);
            throw new Error('Ungültige Server-Antwort');
        }

        if(res.success) renderFiles(res.data);
        else {
             tbody.innerHTML = `<tr><td colspan="5" style="color:red; text-align:center; padding:30px;">Fehler: ${res.error}</td></tr>`;
        }
    } catch(e) {
        console.error(e);
        tbody.innerHTML = `<tr><td colspan="5" style="color:red; text-align:center; padding:30px;">Fehler: ${e.message}</td></tr>`;
    }
}

function renderFiles(data) {
    const tbody = document.getElementById('file-list');
    tbody.innerHTML = '';
    
    if(data.folders.length === 0 && data.files.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:40px; font-style:italic;">Keine Dateien gefunden.</td></tr>';
        return;
    }
    
    data.folders.sort((a,b) => a.name.localeCompare(b.name)).forEach(f => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-size:1.5rem;">📂</td>
            <td><a href="#" onclick="event.preventDefault(); loadPath('${f.path}')" style="font-weight:600; color:#4a5568; text-decoration:none;">${f.name}</a></td>
            <td><span style="background:#edf2f7; color:#4a5568; padding:2px 8px; border-radius:4px; font-size:0.75em;">Ordner</span></td>
            <td>-</td>
            <td style="text-align:right;">
                <button class="btn btn-outline btn-sm" onclick="openRename('${f.path}', '${f.name}')">✎</button>
                <button class="btn btn-danger btn-sm" onclick="deleteItem('${f.path}')">🗑️</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    data.files.sort((a,b) => a.name.localeCompare(b.name)).forEach(f => {
        const tr = document.createElement('tr');
        const icon = getFileIcon(f.type);
        const iconHtml = f.type.match(/jpg|jpeg|png|gif|webp/) 
            ? `<img src="${f.url}" style="width:32px; height:32px; object-fit:cover; border-radius:4px; border:1px solid #e2e8f0;">`
            : `<span style="font-size:1.5rem;">${icon}</span>`;
            
        tr.innerHTML = `
            <td>${iconHtml}</td>
            <td><a href="${f.url}" target="_blank" style="color:#667eea; text-decoration:none;">${f.name}</a></td>
            <td>${f.type.toUpperCase()}</td>
            <td>${formatSize(f.size)}</td>
            <td style="text-align:right;">
                <button class="btn btn-outline btn-sm" onclick="openRename('${f.path}', '${f.name}')">✎</button>
                <button class="btn btn-danger btn-sm" onclick="deleteItem('${f.path}')">🗑️</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function updateBreadcrumb(path) {
    const container = document.getElementById('breadcrumb');
    if(!path) { container.innerHTML = '<span onclick="loadPath(\'\')">Home</span>'; return; }
    
    const parts = path.split('/');
    let html = '<span onclick="loadPath(\'\')">Home</span>';
    parts.forEach((p, i) => {
        const full = parts.slice(0, i+1).join('/');
        html += ` <span style="color:#a0aec0;">/</span> <span onclick="loadPath('${full}')">${p}</span>`;
    });
    container.innerHTML = html;
}

async function handleMemberUpload(input) {
    if(input.files.length === 0) return;
    const btn = document.querySelector('.header-actions .btn-primary');
    const oldText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Upload läuft...';
    
    for(let f of input.files) {
        const fd = new FormData();
        fd.append('action', 'upload_file');
        fd.append('file', f);
        fd.append('path', currentPath); 
        
        try {
            const resp = await fetch(AJAX_URL, {method:'POST', body:fd});
            const res = await resp.json();
            if(!res.success) alert('Fehler bei ' + f.name + ': ' + res.error);
        } catch(e) { alert('Netzwerkfehler'); }
    }
    
    input.value = '';
    btn.disabled = false;
    btn.innerHTML = oldText;
    loadPath(currentPath);
}

function openCreateFolderModal() {
    document.getElementById('create-folder-modal').classList.add('active');
    setTimeout(() => document.getElementById('new-folder-name').focus(), 100);
}

async function createFolder() {
    const name = document.getElementById('new-folder-name').value;
    if(!name) return;
    
    const fd = new FormData();
    fd.append('action', 'create_folder');
    fd.append('name', name);
    fd.append('path', currentPath);
    
    try {
        const resp = await fetch(AJAX_URL, {method:'POST', body:fd});
        const res = await resp.json();
        if(res.success) {
            closeModal('create-folder-modal');
            document.getElementById('new-folder-name').value = '';
            loadPath(currentPath);
        } else {
            alert(res.error);
        }
    } catch(e) { alert('Netzwerkfehler'); }
}

async function deleteItem(path) {
    if(!confirm('Wirklich löschen? Dies kann nicht rückgängig gemacht werden.')) return;
    const fd = new FormData();
    fd.append('action', 'delete_item');
    fd.append('item_path', path);
    
    try {
        const resp = await fetch(AJAX_URL, {method:'POST', body:fd});
        if((await resp.json()).success) loadPath(currentPath);
    } catch(e) { alert('Fehler'); }
}

function openRename(path, name) {
    document.getElementById('rename-old-path').value = path;
    document.getElementById('rename-new-name').value = name;
    document.getElementById('rename-modal').classList.add('active');
}

async function renameItem() {
    const oldPath = document.getElementById('rename-old-path').value;
    const newName = document.getElementById('rename-new-name').value;
    const fd = new FormData();
    fd.append('action', 'rename_item');
    fd.append('old_path', oldPath);
    fd.append('new_name', newName);
    
    try {
        const resp = await fetch(AJAX_URL, {method:'POST', body:fd});
        if((await resp.json()).success) {
            closeModal('rename-modal');
            loadPath(currentPath);
        }
    } catch(e) { alert('Fehler'); }
}

function applyFilter() {
    const filter = document.getElementById('search-input').value.toLowerCase();
    const rows = document.querySelectorAll('#file-list tr');
    rows.forEach(r => {
        const name = r.querySelector('td:nth-child(2) a').innerText.toLowerCase();
        if(name.includes(filter)) r.style.display = '';
        else r.style.display = 'none';
    });
}

function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function formatSize(b) { 
    if(b===0) return '0 B';
    const i = Math.floor(Math.log(b) / Math.log(1024));
    return (b / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['B', 'KB', 'MB', 'GB'][i];
}
function getFileIcon(type) { 
    if(['jpg','jpeg','png','gif','webp'].includes(type)) return '🖼️';
    if(['pdf','doc','docx','txt','xls','xlsx'].includes(type)) return '📄';
    if(['mp4','mov','avi','mkv'].includes(type)) return '🎬';
    if(['zip','rar','7z'].includes(type)) return '📦';
    if(['mp3','wav'].includes(type)) return '🎵';
    return '📄'; 
}
</script>
</body>
</html>

