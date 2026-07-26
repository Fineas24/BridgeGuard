<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poze Colectate — BRIDGEGUARD</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .explorer {
            background: var(--clr-bg-alt);
            border: 1px solid var(--clr-border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 48px;
        }

        .ex-toolbar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: rgba(0,0,0,0.25);
            border-bottom: 1px solid var(--clr-border);
            flex-wrap: wrap;
        }
        .ex-breadcrumb {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            min-width: 0;
            font-family: var(--font-mono);
            font-size: 0.75rem;
        }
        .ex-breadcrumb a {
            color: var(--clr-accent);
            text-decoration: none;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background 0.15s;
            white-space: nowrap;
        }
        .ex-breadcrumb a:hover { background: var(--clr-accent-dim); }
        .ex-breadcrumb .sep { color: var(--clr-muted); font-size: 0.65rem; }
        .ex-breadcrumb .current { color: var(--clr-text); }

        .ex-btn {
            background: var(--clr-surface-2);
            border: 1px solid var(--clr-border);
            color: var(--clr-text-body);
            font-family: var(--font-mono);
            font-size: 0.72rem;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .ex-btn:hover {
            border-color: var(--clr-accent);
            color: var(--clr-accent);
            background: var(--clr-accent-dim);
        }

        .ex-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            padding: 20px;
            min-height: 200px;
        }

        .ex-grid.drag-over-external {
            background: rgba(74, 142, 255, 0.03);
            outline: 2px dashed var(--clr-accent);
            outline-offset: -4px;
        }

        .ex-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: var(--clr-muted);
            font-family: var(--font-mono);
            font-size: 0.8rem;
        }
        .ex-empty-icon { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.35; }

        .ex-folder {
            background: var(--clr-surface);
            border: 1px solid var(--clr-border);
            border-radius: 12px;
            padding: 16px 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s var(--ease-spring);
            position: relative;
        }
        .ex-folder:hover {
            border-color: var(--clr-border-h);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        .ex-folder.drag-over {
            border-color: var(--clr-accent);
            background: var(--clr-accent-dim);
            box-shadow: 0 0 20px rgba(74, 142, 255, 0.15);
        }
        .ex-folder-icon { font-size: 2.2rem; margin-bottom: 8px; }
        .ex-folder-name {
            font-family: var(--font-mono);
            font-size: 0.72rem;
            color: var(--clr-text);
            word-break: break-word;
        }
        .ex-folder-count {
            font-family: var(--font-mono);
            font-size: 0.6rem;
            color: var(--clr-muted);
            margin-top: 4px;
        }
        .ex-folder-del {
            position: absolute;
            top: 6px;
            right: 8px;
            background: none;
            border: none;
            color: var(--clr-muted);
            font-size: 0.7rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.15s;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .ex-folder:hover .ex-folder-del { opacity: 1; }
        .ex-folder-del:hover { color: var(--danger); background: rgba(255,59,92,0.1); }

        .ex-photo {
            background: var(--clr-surface);
            border: 1px solid var(--clr-border);
            border-radius: 10px;
            overflow: hidden;
            cursor: grab;
            transition: all 0.2s;
            position: relative;
        }
        .ex-photo:hover {
            border-color: var(--clr-border-h);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        .ex-photo.dragging {
            opacity: 0.4;
            transform: scale(0.95);
        }
        .ex-photo.selected {
            border-color: var(--clr-accent);
            box-shadow: 0 0 0 2px rgba(74, 142, 255, 0.3);
        }
        .ex-photo img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            display: block;
            pointer-events: none;
        }
        .ex-photo-name {
            padding: 8px;
            font-family: var(--font-mono);
            font-size: 0.58rem;
            color: var(--clr-subtle);
            text-align: center;
            border-top: 1px solid var(--clr-border);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ex-photo-check {
            position: absolute;
            top: 6px;
            left: 6px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            border: 2px solid rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.15s;
            cursor: pointer;
            font-size: 0.6rem;
            color: #fff;
        }
        .ex-photo:hover .ex-photo-check { opacity: 1; }
        .ex-photo.selected .ex-photo-check {
            opacity: 1;
            background: var(--clr-accent);
            border-color: var(--clr-accent);
        }

        .ex-selection-bar {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            background: rgba(74, 142, 255, 0.06);
            border-top: 1px solid var(--clr-border);
            font-family: var(--font-mono);
            font-size: 0.72rem;
            color: var(--clr-accent);
        }
        .ex-selection-bar.visible { display: flex; }

        .ex-new-folder-input {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--clr-accent);
            color: var(--clr-text);
            font-family: var(--font-mono);
            font-size: 0.8rem;
            padding: 8px 14px;
            border-radius: 8px;
            width: 220px;
            outline: none;
        }

        .ex-lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(6, 10, 19, 0.95);
            backdrop-filter: blur(12px);
            z-index: 9500;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            cursor: pointer;
        }
        .ex-lightbox.active { display: flex; }
        .ex-lightbox img {
            max-width: 90vw;
            max-height: 80vh;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
        }
        .ex-lightbox-caption {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-subtle);
            margin-top: 16px;
        }

        .ex-drop-hint {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(74, 142, 255, 0.05);
            border: 2px dashed var(--clr-accent);
            border-radius: 14px;
            z-index: 10;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            font-family: var(--font-mono);
            font-size: 0.85rem;
            color: var(--clr-accent);
        }

        @media (max-width: 600px) {
            .ex-grid { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 8px; padding: 12px; }
            .ex-photo img { height: 75px; }
        }
    </style>
</head>
<body>

<div class="cursor-glow" id="cursorGlow"></div>

<nav class="navbar">
    <div class="nav-brand">
        <span class="nav-brand-title">BRIDGEGUARD</span>
        <?php if (!empty($_SESSION['nume_pod'])): ?>
            <span class="nav-brand-pod"><?= htmlspecialchars($_SESSION['nume_pod']) ?></span>
        <?php endif; ?>
    </div>
    <div class="nav-center">
        <div class="dropdown">
            <button class="dropbtn">Menu</button>
            <div class="dropdown-menu">
                <a href="dashboard.php">Acasă</a>
                <a href="camera.php">Cameră</a>
                <a href="camera_esp.php">ESP32-CAM Sub Pod</a>
                <a href="camera_esp2.php">Camera #2</a>
                <a href="galerie.php" style="color:var(--clr-accent);">Poze Colectate</a>
                <a href="poze_detectii.php">Detectii Semafor</a>
                <a href="control_motoare.php">Control Motoare</a>
                <a href="robot_inspector.php">Robot Inspector</a>
                <a class="disabled">Rapoarte</a>
            </div>
        </div>
    </div>
    <div class="nav-right">
        <div class="dropdown">
            <button class="dropbtn"><?= htmlspecialchars($_SESSION['user']) ?> ▾</button>
            <div class="dropdown-menu">
                <a href="settings.php">Setări cont</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<main class="container">

    <a href="dashboard.php" class="back-link">← Dashboard</a>

    <div class="section-title">Poze Colectate</div>

    <div class="explorer" id="explorer">
        <div class="ex-toolbar">
            <div class="ex-breadcrumb" id="breadcrumb">
                <a onclick="navigate('')">Poze</a>
            </div>
            <button class="ex-btn" id="btnNewFolder" onclick="showNewFolder()">+ Folder Nou</button>
        </div>

        <div class="ex-grid" id="exGrid"
             ondragover="handleExternalDragOver(event)"
             ondragleave="handleExternalDragLeave(event)"
             ondrop="handleExternalDrop(event)">
        </div>

        <div class="ex-selection-bar" id="selectionBar">
            <span id="selCount">0 selectate</span>
            <button class="ex-btn" onclick="deselectAll()">Deselectează</button>
            <button class="ex-btn" onclick="deleteSelected()" style="color:var(--danger);border-color:rgba(255,59,92,0.3);">Șterge</button>
        </div>
    </div>

</main>

<div class="ex-lightbox" id="lightbox" onclick="closeLightbox()">
    <img id="lbImg" src="">
    <div class="ex-lightbox-caption" id="lbCaption"></div>
</div>

<script>
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top = e.clientY + 'px';
});

let currentPath = '';
let selected = new Set();
let draggedFiles = [];

function navigate(path) {
    currentPath = path;
    selected.clear();
    updateSelectionBar();
    loadFolder(path);
}

async function loadFolder(path) {
    const grid = document.getElementById('exGrid');
    grid.innerHTML = '<div class="ex-empty"><div class="ex-empty-icon">⏳</div>Se încarcă...</div>';

    try {
        const r = await fetch(`api_galerie.php?action=list&path=${encodeURIComponent(path)}`);
        const data = await r.json();
        if (data.error) throw new Error(data.error);
        renderBreadcrumb(data.breadcrumb);
        renderGrid(data.folders, data.files);
    } catch (err) {
        grid.innerHTML = `<div class="ex-empty"><div class="ex-empty-icon">⚠</div>${err.message}</div>`;
    }
}

function renderBreadcrumb(crumbs) {
    const el = document.getElementById('breadcrumb');
    let html = '<a onclick="navigate(\'\')">Poze</a>';
    crumbs.forEach(c => {
        html += `<span class="sep">›</span><a onclick="navigate('${esc(c.path)}')">${esc(c.name)}</a>`;
    });
    el.innerHTML = html;
}

function renderGrid(folders, files) {
    const grid = document.getElementById('exGrid');

    if (folders.length === 0 && files.length === 0) {
        grid.innerHTML = `<div class="ex-empty">
            <div class="ex-empty-icon">📂</div>
            Folder gol<br><span style="font-size:0.65rem;opacity:0.6;">Trage poze aici sau creează un folder nou</span>
        </div>`;
        return;
    }

    let html = '';

    folders.forEach(f => {
        html += `<div class="ex-folder"
                      ondragover="event.preventDefault(); this.classList.add('drag-over')"
                      ondragleave="this.classList.remove('drag-over')"
                      ondrop="dropOnFolder(event, '${esc(f.path)}')"
                      onclick="navigate('${esc(f.path)}')">
            <button class="ex-folder-del" onclick="event.stopPropagation(); deleteFolder('${esc(f.path)}')" title="Șterge folder">✕</button>
            <div class="ex-folder-icon">📁</div>
            <div class="ex-folder-name">${esc(f.name)}</div>
            <div class="ex-folder-count">${f.count} poze</div>
        </div>`;
    });

    files.forEach(f => {
        const isSelected = selected.has(f.path);
        html += `<div class="ex-photo ${isSelected ? 'selected' : ''}"
                      draggable="true"
                      data-path="${esc(f.path)}"
                      ondragstart="startDrag(event, '${esc(f.path)}')"
                      ondragend="endDrag(event)"
                      onclick="handlePhotoClick(event, '${esc(f.path)}')">
            <div class="ex-photo-check" onclick="event.stopPropagation(); toggleSelect('${esc(f.path)}')">${isSelected ? '✓' : ''}</div>
            <img src="${f.url}" alt="${esc(f.name)}" loading="lazy"
                 ondblclick="event.stopPropagation(); openLightbox('${f.url}', '${esc(f.name)}')">
            <div class="ex-photo-name" title="${esc(f.name)}">${esc(f.date)}</div>
        </div>`;
    });

    grid.innerHTML = html;
}

function handlePhotoClick(e, path) {
    if (e.ctrlKey || e.metaKey) {
        toggleSelect(path);
    } else if (selected.size > 0) {
        toggleSelect(path);
    } else {
        const el = document.querySelector(`[data-path="${CSS.escape(path)}"]`);
        const img = el?.querySelector('img');
        if (img) openLightbox(img.src, path.split('/').pop());
    }
}

function toggleSelect(path) {
    if (selected.has(path)) selected.delete(path);
    else selected.add(path);
    updateSelectionUI();
}

function deselectAll() {
    selected.clear();
    updateSelectionUI();
}

function updateSelectionUI() {
    document.querySelectorAll('.ex-photo').forEach(el => {
        const p = el.dataset.path;
        const check = el.querySelector('.ex-photo-check');
        if (selected.has(p)) {
            el.classList.add('selected');
            check.textContent = '✓';
        } else {
            el.classList.remove('selected');
            check.textContent = '';
        }
    });
    updateSelectionBar();
}

function updateSelectionBar() {
    const bar = document.getElementById('selectionBar');
    if (selected.size > 0) {
        bar.classList.add('visible');
        document.getElementById('selCount').textContent = `${selected.size} selectat${selected.size > 1 ? 'e' : 'ă'}`;
    } else {
        bar.classList.remove('visible');
    }
}

function startDrag(e, path) {
    if (selected.has(path)) {
        draggedFiles = [...selected];
    } else {
        draggedFiles = [path];
    }
    e.dataTransfer.setData('text/plain', 'internal');
    e.target.classList.add('dragging');
}

function endDrag(e) {
    e.target.classList.remove('dragging');
    document.querySelectorAll('.ex-folder').forEach(f => f.classList.remove('drag-over'));
}

async function dropOnFolder(e, folderPath) {
    e.preventDefault();
    e.stopPropagation();
    document.querySelectorAll('.ex-folder').forEach(f => f.classList.remove('drag-over'));

    if (e.dataTransfer.getData('text/plain') === 'internal' && draggedFiles.length > 0) {
        await moveFiles(draggedFiles, folderPath);
        selected.clear();
        draggedFiles = [];
        loadFolder(currentPath);
        return;
    }

    if (e.dataTransfer.files.length > 0) {
        await uploadFiles(e.dataTransfer.files, folderPath);
        loadFolder(currentPath);
    }
}

function handleExternalDragOver(e) {
    if (e.dataTransfer.types.includes('Files')) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over-external');
    }
}
function handleExternalDragLeave(e) {
    e.currentTarget.classList.remove('drag-over-external');
}
async function handleExternalDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over-external');

    if (e.dataTransfer.getData('text/plain') === 'internal') return;

    if (e.dataTransfer.files.length > 0) {
        await uploadFiles(e.dataTransfer.files, currentPath);
        loadFolder(currentPath);
    }
}

async function uploadFiles(fileList, destPath) {
    for (const file of fileList) {
        if (!file.type.match(/image\/(jpeg|png)/)) continue;
        const formData = new FormData();
        formData.append('file', file);
        formData.append('destination', destPath);
        await fetch('api_galerie_upload.php', { method: 'POST', body: formData });
    }
}

async function moveFiles(files, dest) {
    await fetch('api_galerie.php?action=move', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ files, destination: dest })
    });
}

function showNewFolder() {
    const name = prompt('Nume folder nou:');
    if (!name || !name.trim()) return;
    createFolder(name.trim());
}

async function createFolder(name) {
    const r = await fetch('api_galerie.php?action=create_folder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ parent: currentPath, name })
    });
    const data = await r.json();
    if (data.error) alert(data.error);
    else loadFolder(currentPath);
}

async function deleteFolder(path) {
    if (!confirm('Ștergi acest folder? (trebuie să fie gol)')) return;
    const r = await fetch('api_galerie.php?action=delete_folder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ path })
    });
    const data = await r.json();
    if (data.error) alert(data.error);
    else loadFolder(currentPath);
}

async function deleteSelected() {
    if (!confirm(`Ștergi ${selected.size} fișier(e)?`)) return;
    for (const path of selected) {
        await fetch('api_galerie.php?action=delete_file', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ path })
        });
    }
    selected.clear();
    loadFolder(currentPath);
}

function openLightbox(src, caption) {
    document.getElementById('lbImg').src = src;
    document.getElementById('lbCaption').textContent = caption;
    document.getElementById('lightbox').classList.add('active');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
}

function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

navigate('');
</script>
</body>
</html>
