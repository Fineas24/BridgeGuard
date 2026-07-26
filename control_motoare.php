<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Motoare — BRIDGEGUARD</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .motors-hero {
            text-align: center;
            margin-bottom: 48px;
        }
        .motors-hero h1 {
            font-family: var(--font-display);
            font-size: 1.8rem;
            letter-spacing: 2px;
            background: linear-gradient(135deg, var(--clr-accent), #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        .motors-hero p {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-subtle);
            letter-spacing: 0.05em;
        }

        .motors-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            max-width: 800px;
            margin: 0 auto 48px;
        }

        .motor-card {
            background: linear-gradient(135deg, rgba(17, 26, 46, 0.8), rgba(12, 18, 34, 0.9));
            border: 1px solid var(--clr-border);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            transition: all var(--dur-base) var(--ease-spring);
        }
        .motor-card:hover {
            border-color: var(--clr-border-h);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 217, 126, 0.08);
        }

        .motor-label {
            font-family: var(--font-display);
            font-size: 1rem;
            letter-spacing: 1.5px;
            color: var(--clr-text);
            margin-bottom: 4px;
        }
        .motor-pos {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--clr-subtle);
            margin-bottom: 16px;
        }

        .motor-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .arrow-btn {
            width: 56px;
            height: 56px;
            border: 1px solid var(--clr-border);
            border-radius: 12px;
            background: rgba(0, 217, 126, 0.06);
            color: var(--clr-accent);
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .arrow-btn:hover {
            background: rgba(0, 217, 126, 0.15);
            border-color: var(--clr-accent);
            box-shadow: 0 0 15px rgba(0, 217, 126, 0.2);
            transform: scale(1.08);
        }
        .arrow-btn:active {
            transform: scale(0.95);
            background: rgba(0, 217, 126, 0.25);
        }
        .arrow-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
            transform: none;
        }

        .step-display {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--clr-subtle);
            min-width: 50px;
        }

        .step-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
        }
        .step-selector label {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-subtle);
            letter-spacing: 0.05em;
        }
        .step-selector input {
            width: 80px;
            padding: 8px 12px;
            border: 1px solid var(--clr-border);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            color: var(--clr-accent);
            font-family: var(--font-mono);
            font-size: 0.85rem;
            text-align: center;
            outline: none;
            transition: border-color 0.2s;
        }
        .step-selector input:focus {
            border-color: var(--clr-accent);
        }

        .home-section {
            text-align: center;
            margin-bottom: 48px;
        }
        .home-btn {
            padding: 12px 32px;
            border: 1px solid rgba(255, 59, 92, 0.4);
            border-radius: 12px;
            background: rgba(255, 59, 92, 0.08);
            color: #ff3b5c;
            font-family: var(--font-display);
            font-size: 0.85rem;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .home-btn:hover {
            background: rgba(255, 59, 92, 0.18);
            border-color: #ff3b5c;
            box-shadow: 0 0 20px rgba(255, 59, 92, 0.2);
        }
        .home-btn:active {
            transform: scale(0.96);
        }

        .status-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ff3b5c;
            transition: background 0.3s;
        }
        .status-dot.online {
            background: var(--clr-accent);
            box-shadow: 0 0 8px var(--clr-accent);
            animation: live-blink 1.5s ease-in-out infinite;
        }
        @keyframes live-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .status-text {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--clr-subtle);
        }

        .log-section {
            max-width: 800px;
            margin: 0 auto 48px;
        }
        .log-title {
            font-family: var(--font-display);
            font-size: 0.85rem;
            letter-spacing: 1.5px;
            color: var(--clr-subtle);
            margin-bottom: 12px;
        }
        .log-box {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--clr-border);
            border-radius: 12px;
            padding: 16px;
            max-height: 200px;
            overflow-y: auto;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--clr-subtle);
            line-height: 1.8;
        }
        .log-box::-webkit-scrollbar { width: 4px; }
        .log-box::-webkit-scrollbar-thumb { background: var(--clr-border); border-radius: 2px; }

        .log-entry { padding: 2px 0; }
        .log-entry.success { color: var(--clr-accent); }
        .log-entry.error { color: #ff3b5c; }

        .cam-section {
            max-width: 800px;
            margin: 0 auto 48px;
        }
        .cam-card {
            background: linear-gradient(135deg, rgba(17, 26, 46, 0.8), rgba(12, 18, 34, 0.9));
            border: 1px solid var(--clr-border);
            border-radius: 16px;
            overflow: hidden;
            transition: all var(--dur-base) var(--ease-spring);
        }
        .cam-card:hover {
            border-color: var(--clr-border-h);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), 0 0 20px rgba(74, 142, 255, 0.05);
        }
        .cam-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--clr-border);
            background: rgba(0, 0, 0, 0.2);
        }
        .cam-card-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ff3b5c;
            box-shadow: 0 0 8px #ff3b5c;
            animation: live-blink 1.5s ease-in-out infinite;
        }
        .cam-card-title {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-text);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .cam-card-body {
            position: relative;
            background: #000;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cam-card-body img {
            width: 100%;
            height: auto;
            display: block;
        }
        .cam-offline {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--clr-subtle);
            padding: 40px;
        }
        .cam-offline-icon {
            font-size: 2rem;
            opacity: 0.4;
        }

        @media (max-width: 600px) {
            .motors-grid { grid-template-columns: 1fr; }
            .arrow-btn { width: 48px; height: 48px; font-size: 1.2rem; }
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
                <a href="dashboard.php">Acasa</a>
                <a href="camera.php">Camera</a>
                <a href="camera_esp.php">ESP32-CAM Sub Pod</a>
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
            <button class="dropbtn"><?= htmlspecialchars($_SESSION['user']) ?> &#9662;</button>
            <div class="dropdown-menu">
                <a href="settings.php">Setari cont</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<main class="container">

    <div class="motors-hero">
        <h1>CONTROL MOTOARE STEPPER</h1>
        <p>SISTEM ALINIERE &mdash; 4 MOTOARE VIA ARDUINO</p>
    </div>

    <div class="status-bar">
        <div class="status-dot" id="statusDot"></div>
        <span class="status-text" id="statusText">Se conecteaza...</span>
    </div>

    <div class="step-selector">
        <label>PASI PER CLICK:</label>
        <input type="number" id="stepSize" value="50" min="1" max="5000">
    </div>

    <div class="motors-grid">
        <?php for ($i = 1; $i <= 4; $i++): ?>
        <div class="motor-card">
            <div class="motor-label">MOTOR <?= $i ?></div>
            <div class="motor-pos" id="pos<?= $i ?>">Pozitie: 0</div>
            <div class="motor-controls">
                <button class="arrow-btn" onclick="sendMotor(<?= $i ?>, -1)" title="Inapoi">&#9664;</button>
                <div class="step-display" id="steps<?= $i ?>">0 pasi</div>
                <button class="arrow-btn" onclick="sendMotor(<?= $i ?>, 1)" title="Inainte">&#9654;</button>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <div class="home-section">
        <button class="home-btn" onclick="sendHome()">&#8962; RESETARE POZITIE (HOME)</button>
    </div>

    <div class="cam-section">
        <div class="cam-card">
            <div class="cam-card-header">
                <div class="cam-card-dot" id="camDot"></div>
                <span class="cam-card-title">Camera Sub Pod &mdash; ESP32-CAM</span>
            </div>
            <div class="cam-card-body">
                <img id="camStream" src="http://192.168.100.55:81/stream" alt="Camera Sub Pod"
                     style="display:none;"
                     onload="this.style.display='block'; document.getElementById('camOffline').style.display='none'; document.getElementById('camDot').style.background='var(--clr-accent)'; document.getElementById('camDot').style.boxShadow='0 0 8px var(--clr-accent)';"
                     onerror="this.style.display='none'; document.getElementById('camOffline').style.display='flex';">
                <div class="cam-offline" id="camOffline">
                    <div class="cam-offline-icon">&#128249;</div>
                    Se conecteaza la ESP32-CAM...
                </div>
            </div>
        </div>
    </div>

    <div class="log-section">
        <div class="log-title">JURNAL COMENZI</div>
        <div class="log-box" id="logBox"></div>
    </div>

</main>

<script>
const API = 'api_motoare.php';
let busy = false;

function getSteps() {
    return parseInt(document.getElementById('stepSize').value) || 50;
}

async function sendMotor(motor, direction) {
    if (busy) return;
    busy = true;
    disableButtons(true);

    const steps = getSteps() * direction;
    const cmd = motor + ':' + steps;
    addLog('> ' + cmd, '');

    try {
        const res = await fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({command: cmd})
        });
        const data = await res.json();
        if (data.ok) {
            addLog(data.response || 'OK', 'success');
            updatePositions(data.positions);
        } else {
            addLog('EROARE: ' + (data.error || 'necunoscuta'), 'error');
        }
    } catch (e) {
        addLog('EROARE: ' + e.message, 'error');
    }

    busy = false;
    disableButtons(false);
}

async function sendHome() {
    if (busy) return;
    if (!confirm('Resetezi toate motoarele la pozitia 0?')) return;
    busy = true;
    disableButtons(true);
    addLog('> HOME (0)', '');

    try {
        const res = await fetch(API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({command: '0'})
        });
        const data = await res.json();
        if (data.ok) {
            addLog(data.response || 'Home OK', 'success');
            updatePositions(data.positions);
        } else {
            addLog('EROARE: ' + (data.error || 'necunoscuta'), 'error');
        }
    } catch (e) {
        addLog('EROARE: ' + e.message, 'error');
    }

    busy = false;
    disableButtons(false);
}

function updatePositions(positions) {
    if (!positions) return;
    for (let i = 0; i < 4; i++) {
        document.getElementById('pos' + (i + 1)).textContent = 'Pozitie: ' + positions[i];
        document.getElementById('steps' + (i + 1)).textContent = positions[i] + ' pasi';
    }
}

function disableButtons(state) {
    document.querySelectorAll('.arrow-btn, .home-btn').forEach(b => b.disabled = state);
}

function addLog(msg, type) {
    const box = document.getElementById('logBox');
    const entry = document.createElement('div');
    entry.className = 'log-entry' + (type ? ' ' + type : '');
    const now = new Date().toLocaleTimeString('ro-RO');
    entry.textContent = '[' + now + '] ' + msg;
    box.prepend(entry);
    while (box.children.length > 50) box.removeChild(box.lastChild);
}

async function checkStatus() {
    try {
        const res = await fetch(API);
        const data = await res.json();
        const dot = document.getElementById('statusDot');
        const txt = document.getElementById('statusText');
        if (data.connected) {
            dot.classList.add('online');
            txt.textContent = 'Arduino conectat';
            if (data.positions) updatePositions(data.positions);
        } else {
            dot.classList.remove('online');
            txt.textContent = data.error || 'Deconectat';
        }
    } catch {
        document.getElementById('statusDot').classList.remove('online');
        document.getElementById('statusText').textContent = 'Serverul Python nu ruleaza';
    }
}

checkStatus();
setInterval(checkStatus, 5000);

document.addEventListener('mousemove', e => {
    const g = document.getElementById('cursorGlow');
    if (g) { g.style.left = e.clientX + 'px'; g.style.top = e.clientY + 'px'; }
});
</script>

</body>
</html>
