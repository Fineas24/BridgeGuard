<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32-CAM — BRIDGEGUARD</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .cam-hero {
            text-align: center;
            margin-bottom: 48px;
        }
        .cam-hero h1 {
            font-family: var(--font-display);
            font-size: 1.8rem;
            letter-spacing: 2px;
            background: linear-gradient(135deg, var(--clr-accent), var(--clr-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        .cam-hero p {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-subtle);
            letter-spacing: 0.05em;
        }

        .cam-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 32px;
        }
        .btn-capture {
            background: linear-gradient(135deg, var(--clr-accent), var(--clr-blue));
            color: #060a13;
            font-family: var(--font-body);
            font-weight: 700;
            font-size: 1rem;
            padding: 16px 40px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            transition: all var(--dur-base) var(--ease-spring);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }
        .btn-capture:hover {
            box-shadow: 0 0 40px rgba(0, 217, 126, 0.4), 0 0 80px rgba(0, 217, 126, 0.15);
            transform: scale(1.04);
        }
        .btn-capture:active { transform: scale(0.98); }
        .btn-capture:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .cam-status {
            text-align: center;
            margin-bottom: 24px;
            min-height: 40px;
        }
        .cam-status-msg {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            padding: 10px 20px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .cam-status-msg.info {
            color: var(--clr-accent);
            background: var(--clr-accent-dim);
            border: 1px solid rgba(0, 217, 126, 0.15);
        }
        .cam-status-msg.success {
            color: var(--accent);
            background: rgba(0, 200, 150, 0.1);
            border: 1px solid rgba(0, 200, 150, 0.2);
        }
        .cam-status-msg.warning {
            color: var(--warning);
            background: rgba(255, 170, 0, 0.1);
            border: 1px solid rgba(255, 170, 0, 0.2);
        }
        .cam-status-msg.error {
            color: var(--danger);
            background: rgba(255, 59, 92, 0.08);
            border: 1px solid rgba(255, 59, 92, 0.2);
        }

        .cam-panel {
            background: linear-gradient(135deg, rgba(17, 26, 46, 0.8), rgba(12, 18, 34, 0.9));
            border: 1px solid var(--clr-border);
            border-radius: 16px;
            overflow: hidden;
            transition: all var(--dur-base) var(--ease-spring);
        }
        .cam-panel:hover {
            border-color: var(--clr-border-h);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 217, 126, 0.05);
        }
        .cam-panel-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--clr-border);
            background: rgba(0, 0, 0, 0.2);
        }
        .cam-panel-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .cam-panel-dot.live { background: #ff3b5c; box-shadow: 0 0 8px #ff3b5c; animation: live-blink 1.5s ease-in-out infinite; }
        .cam-panel-dot.photo { background: var(--clr-accent); box-shadow: 0 0 8px var(--clr-accent); }
        .cam-panel-dot.detect { background: #ffaa00; box-shadow: 0 0 8px #ffaa00; }
        @keyframes live-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .cam-panel-title {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-text);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .cam-panel-body {
            position: relative;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }
        .cam-panel-body img {
            width: 100%;
            height: auto;
            max-height: 600px;
            object-fit: contain;
            display: block;
        }
        .cam-placeholder {
            color: var(--clr-muted);
            font-family: var(--font-mono);
            font-size: 0.8rem;
            text-align: center;
            padding: 40px;
        }
        .cam-placeholder-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
            opacity: 0.4;
        }

        .cam-row-top {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        .cam-row-bottom {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 48px;
        }

        .detection-result {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            padding: 6px 14px;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        .detection-result.found {
            background: rgba(255, 59, 92, 0.9);
            color: #fff;
            box-shadow: 0 0 15px rgba(255, 59, 92, 0.4);
        }
        .detection-result.clean {
            background: rgba(0, 200, 150, 0.9);
            color: #fff;
            box-shadow: 0 0 15px rgba(0, 200, 150, 0.4);
        }

        .cam-loading {
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 40px;
        }
        .cam-loading.active { display: flex; }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--clr-border);
            border-top-color: var(--clr-accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner-text {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-subtle);
        }

        .cam-panel-info {
            padding: 10px 20px;
            border-top: 1px solid var(--clr-border);
            font-family: var(--font-mono);
            font-size: 0.68rem;
            color: var(--clr-subtle);
            display: none;
        }
        .cam-panel-info.visible { display: flex; justify-content: space-between; }

        @media (max-width: 768px) {
            .cam-row-bottom { grid-template-columns: 1fr; }
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
                <a href="camera_esp.php" style="color:var(--clr-accent);">ESP32-CAM Sub Pod</a>
                <a href="camera_esp2.php">Camera #2</a>
                <a href="galerie.php">Poze Colectate</a>
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

    <a href="dashboard.php" class="back-link">&larr; Dashboard</a>

    <div class="cam-hero">
        <h1>ESP32-CAM — SUB POD</h1>
        <p>Vizualizare live · Captură foto · Analiză AI fisuri structurale</p>
    </div>

    <div class="cam-status" id="camStatus"></div>

    <div class="cam-actions">
        <button class="btn-capture" id="btnCapture" onclick="captureAndDetect()">
            CAPTURĂ & DETECTARE FISURI
        </button>
    </div>

    <div class="cam-row-top">
        <div class="cam-panel">
            <div class="cam-panel-header">
                <div class="cam-panel-dot live"></div>
                <span class="cam-panel-title">LIVE STREAM — ESP32-CAM</span>
            </div>
            <div class="cam-panel-body" id="livePanel">
                <img id="liveStream" src="" alt="Live Stream"
                     style="display:none;"
                     onerror="this.style.display='none'; document.getElementById('liveOffline').style.display='flex';"
                     onload="this.style.display='block'; document.getElementById('liveOffline').style.display='none';">
                <div class="cam-placeholder" id="liveOffline">
                    <div class="cam-placeholder-icon">📹</div>
                    Se conectează la ESP32-CAM...<br>
                    <span style="font-size:0.65rem;opacity:0.6;">Verifică dacă ESP32-CAM este pornită și conectată la WiFi</span>
                </div>
            </div>
        </div>
    </div>

    <div class="cam-row-bottom">
        <div class="cam-panel">
            <div class="cam-panel-header">
                <div class="cam-panel-dot photo"></div>
                <span class="cam-panel-title">FOTOGRAFIE CAPTURATĂ</span>
            </div>
            <div class="cam-panel-body" id="capturePanel">
                <img id="capturedImg" src="" alt="Captură" style="display:none;">
                <div class="cam-loading" id="captureLoading">
                    <div class="spinner"></div>
                    <span class="spinner-text">Se face fotografia...</span>
                </div>
                <div class="cam-placeholder" id="capturePlaceholder">
                    <div class="cam-placeholder-icon">📷</div>
                    Apasă butonul pentru a face o fotografie
                </div>
            </div>
            <div class="cam-panel-info" id="captureInfo">
                <span id="captureDate">—</span>
                <span id="captureFile">—</span>
            </div>
        </div>

        <div class="cam-panel">
            <div class="cam-panel-header">
                <div class="cam-panel-dot detect"></div>
                <span class="cam-panel-title">REZULTAT DETECTARE FISURI</span>
            </div>
            <div class="cam-panel-body" id="detectPanel" style="position:relative;">
                <img id="detectedImg" src="" alt="Detectare" style="display:none;">
                <div class="cam-loading" id="detectLoading">
                    <div class="spinner"></div>
                    <span class="spinner-text">Se analizează cu AI...</span>
                </div>
                <div class="cam-placeholder" id="detectPlaceholder">
                    <div class="cam-placeholder-icon">🔍</div>
                    Rezultatul analizei AI va apărea aici
                </div>
                <div class="detection-result" id="detectionBadge" style="display:none;"></div>
            </div>
            <div class="cam-panel-info" id="detectInfo">
                <span id="detectDate">—</span>
                <span id="detectResult">—</span>
            </div>
        </div>
    </div>

</main>

<script>
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top = e.clientY + 'px';
});

const ESP_STREAM_URL = 'http://192.168.100.55:81/stream';
const liveImg = document.getElementById('liveStream');
liveImg.src = ESP_STREAM_URL;
liveImg.style.display = 'block';

setInterval(() => {
    if (liveImg.naturalWidth === 0) {
        liveImg.src = ESP_STREAM_URL + '?t=' + Date.now();
    }
}, 5000);

function setStatus(msg, type) {
    const el = document.getElementById('camStatus');
    el.innerHTML = `<span class="cam-status-msg ${type}">${msg}</span>`;
    if (type === 'success') {
        setTimeout(() => { el.innerHTML = ''; }, 5000);
    }
}

async function captureAndDetect() {
    const btn = document.getElementById('btnCapture');
    btn.disabled = true;
    btn.textContent = 'SE PROCESEAZĂ...';

    document.getElementById('capturePlaceholder').style.display = 'none';
    document.getElementById('capturedImg').style.display = 'none';
    document.getElementById('captureLoading').classList.add('active');

    document.getElementById('detectPlaceholder').style.display = 'none';
    document.getElementById('detectedImg').style.display = 'none';
    document.getElementById('detectLoading').classList.remove('active');
    document.getElementById('detectionBadge').style.display = 'none';

    setStatus('Se face fotografia de pe ESP32-CAM și se trimite la analiza AI...', 'info');

    try {
        const resp = await fetch('api_camera_esp.php?action=detect', { method: 'POST' });
        const data = await resp.json();

        if (!data.success) {
            throw new Error(data.error || 'Eroare la captura ESP32-CAM');
        }

        document.getElementById('captureLoading').classList.remove('active');
        const capImg = document.getElementById('capturedImg');
        capImg.src = data.capture + '?t=' + Date.now();
        capImg.style.display = 'block';

        document.getElementById('captureInfo').classList.add('visible');
        document.getElementById('captureDate').textContent = data.timestamp.replace(/_/g, ' ');
        document.getElementById('captureFile').textContent = data.cap_filename;

        document.getElementById('detectLoading').classList.add('active');

        await new Promise(r => setTimeout(r, 300));

        document.getElementById('detectLoading').classList.remove('active');
        const detImg = document.getElementById('detectedImg');
        detImg.src = data.detection + '?t=' + Date.now();
        detImg.style.display = 'block';

        document.getElementById('detectInfo').classList.add('visible');
        document.getElementById('detectDate').textContent = data.timestamp.replace(/_/g, ' ');

        const badge = document.getElementById('detectionBadge');
        badge.style.display = 'block';

        if (data.detection_failed) {
            console.error('Detection failed:', data.debug_error);
            badge.textContent = 'EROARE DETECTARE';
            badge.className = 'detection-result found';
            document.getElementById('detectResult').textContent = 'AI indisponibil';
            setStatus('Eroare la analiza AI — verifică consola pentru detalii.', 'error');
        } else if (data.has_cracks) {
            badge.textContent = `FISURI DETECTATE (${data.count})`;
            badge.className = 'detection-result found';
            document.getElementById('detectResult').textContent = `${data.count} fisur(i) detectat(e)`;
            setStatus(`⚠ S-au detectat ${data.count} fisur(i) în imagine!`, 'warning');
        } else {
            badge.textContent = 'FĂRĂ FISURI';
            badge.className = 'detection-result clean';
            document.getElementById('detectResult').textContent = 'Nicio fisură detectată';
            setStatus('Analiza completă — nicio fisură detectată.', 'success');
        }

    } catch (err) {
        document.getElementById('captureLoading').classList.remove('active');
        document.getElementById('detectLoading').classList.remove('active');
        setStatus('Eroare: ' + err.message, 'error');

        if (!document.getElementById('capturedImg').src.includes('uploads')) {
            document.getElementById('capturePlaceholder').style.display = '';
        }
        if (!document.getElementById('detectedImg').src.includes('uploads')) {
            document.getElementById('detectPlaceholder').style.display = '';
        }
    }

    btn.disabled = false;
    btn.textContent = 'CAPTURĂ & DETECTARE FISURI';
}

document.querySelectorAll('.btn-capture').forEach(el => {
    el.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        ripple.classList.add('btn-ripple');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
        this.style.position = this.style.position || 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});
</script>
</body>
</html>
