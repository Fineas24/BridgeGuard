<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Robot Inspector — BRIDGEGUARD</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .robot-hero {
            text-align: center;
            margin-bottom: 48px;
        }
        .robot-hero h1 {
            font-family: var(--font-display);
            font-size: 1.8rem;
            letter-spacing: 2px;
            background: linear-gradient(135deg, var(--clr-accent), #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        .robot-hero p {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-subtle);
            letter-spacing: 0.05em;
        }

        .cams-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            max-width: 1000px;
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
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 217, 126, 0.08);
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
        .cam-card-dot.online {
            background: var(--clr-accent);
            box-shadow: 0 0 8px var(--clr-accent);
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
            aspect-ratio: 4 / 3;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cam-card-body img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            text-align: center;
        }
        .cam-offline-icon {
            font-size: 2rem;
            opacity: 0.4;
        }
        @keyframes live-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .control-zone {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 24px;
            max-width: 1000px;
            margin: 0 auto 48px;
            align-items: stretch;
        }

        .panel-card {
            background: linear-gradient(135deg, rgba(17, 26, 46, 0.8), rgba(12, 18, 34, 0.9));
            border: 1px solid var(--clr-border);
            border-radius: 16px;
            padding: 24px;
            transition: all var(--dur-base) var(--ease-spring);
        }
        .panel-card:hover {
            border-color: var(--clr-border-h);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 217, 126, 0.08);
        }
        .panel-title {
            font-family: var(--font-display);
            font-size: 0.85rem;
            letter-spacing: 1.5px;
            color: var(--clr-subtle);
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .dpad {
            display: grid;
            grid-template-columns: repeat(3, 72px);
            grid-template-rows: repeat(3, 72px);
            gap: 10px;
            justify-content: center;
            margin: 12px auto;
        }
        .dpad-btn {
            border: 1px solid var(--clr-border);
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(0, 217, 126, 0.10), rgba(0, 217, 126, 0.03));
            color: var(--clr-accent);
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            transition: all 0.15s ease;
            user-select: none;
            box-shadow: 0 4px 0 rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }
        .dpad-btn .key-hint {
            font-family: var(--font-mono);
            font-size: 0.55rem;
            color: var(--clr-subtle);
            letter-spacing: 0.05em;
        }
        .dpad-btn:hover {
            border-color: var(--clr-accent);
            background: linear-gradient(180deg, rgba(0, 217, 126, 0.20), rgba(0, 217, 126, 0.08));
            box-shadow: 0 4px 0 rgba(0, 0, 0, 0.4), 0 0 18px rgba(0, 217, 126, 0.25);
            transform: translateY(-1px);
        }
        .dpad-btn:active, .dpad-btn.pressed {
            transform: translateY(3px);
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.4), 0 0 22px rgba(0, 217, 126, 0.35);
            background: linear-gradient(180deg, rgba(0, 217, 126, 0.30), rgba(0, 217, 126, 0.15));
        }
        .dpad-btn.stop {
            border-color: rgba(255, 59, 92, 0.4);
            background: linear-gradient(180deg, rgba(255, 59, 92, 0.12), rgba(255, 59, 92, 0.04));
            color: #ff3b5c;
            font-size: 0.8rem;
            font-family: var(--font-display);
            letter-spacing: 1px;
        }
        .dpad-btn.stop:hover {
            border-color: #ff3b5c;
            box-shadow: 0 4px 0 rgba(0, 0, 0, 0.4), 0 0 18px rgba(255, 59, 92, 0.3);
        }
        .dpad-btn.stop:active, .dpad-btn.stop.pressed {
            transform: translateY(3px);
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.4), 0 0 22px rgba(255, 59, 92, 0.4);
        }
        .dpad-hint {
            text-align: center;
            font-family: var(--font-mono);
            font-size: 0.65rem;
            color: var(--clr-subtle);
            margin-top: 16px;
            letter-spacing: 0.05em;
        }

        .robot-status {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin-bottom: 16px;
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
        .status-text {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: var(--clr-subtle);
        }

        .robot-screen {
            background: #020804;
            border: 1px solid rgba(0, 217, 126, 0.25);
            border-radius: 12px;
            padding: 20px;
            font-family: var(--font-mono);
            position: relative;
            overflow: hidden;
            height: calc(100% - 40px);
            min-height: 240px;
            box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.8), 0 0 15px rgba(0, 217, 126, 0.06);
        }
        .robot-screen::after {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                0deg,
                rgba(0, 217, 126, 0.025) 0px,
                rgba(0, 217, 126, 0.025) 1px,
                transparent 1px,
                transparent 3px
            );
            pointer-events: none;
        }
        .screen-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.6rem;
            color: rgba(0, 217, 126, 0.5);
            letter-spacing: 0.1em;
            border-bottom: 1px solid rgba(0, 217, 126, 0.15);
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .screen-cursor {
            animation: cursor-blink 1s steps(1) infinite;
        }
        @keyframes cursor-blink {
            50% { opacity: 0; }
        }

        .step-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 6px;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: var(--clr-subtle);
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .step-icon {
            width: 18px;
            text-align: center;
            font-size: 0.7rem;
        }
        .step-row.done {
            color: rgba(0, 217, 126, 0.55);
        }
        .step-row.done .step-icon { color: var(--clr-accent); }
        .step-row.active {
            color: var(--clr-accent);
            background: rgba(0, 217, 126, 0.07);
            text-shadow: 0 0 8px rgba(0, 217, 126, 0.5);
        }
        .step-row.active .step-icon {
            animation: live-blink 1s ease-in-out infinite;
        }

        @media (max-width: 800px) {
            .cams-grid { grid-template-columns: 1fr; }
            .control-zone { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .dpad { grid-template-columns: repeat(3, 60px); grid-template-rows: repeat(3, 60px); }
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

    <div class="robot-hero">
        <h1>ROBOT INSPECTOR</h1>
        <p>INSPECTIE FISURI &mdash; CONTROL MANUAL + MONITORIZARE</p>
    </div>

    <div class="cams-grid">
        <div class="cam-card">
            <div class="cam-card-header">
                <div class="cam-card-dot" id="cam1Dot"></div>
                <span class="cam-card-title">CAM 1 &mdash; Frontala</span>
            </div>
            <div class="cam-card-body">
                <img id="cam1Stream" alt="Camera 1" style="display:none;">
                <div class="cam-offline" id="cam1Offline">
                    <div class="cam-offline-icon">&#128247;</div>
                    In asteptarea conexiunii WiFi
                </div>
            </div>
        </div>
        <div class="cam-card">
            <div class="cam-card-header">
                <div class="cam-card-dot" id="cam2Dot"></div>
                <span class="cam-card-title">CAM 2 &mdash; Zona de lucru</span>
            </div>
            <div class="cam-card-body">
                <img id="cam2Stream" alt="Camera 2" style="display:none;">
                <div class="cam-offline" id="cam2Offline">
                    <div class="cam-offline-icon">&#128247;</div>
                    In asteptarea conexiunii WiFi
                </div>
            </div>
        </div>
    </div>

    <div class="control-zone">

        <div class="panel-card">
            <div class="panel-title">Control Deplasare</div>
            <div class="robot-status">
                <div class="status-dot" id="robotDot"></div>
                <span class="status-text" id="robotStatus">Se verifica robotul...</span>
            </div>
            <div class="dpad">
                <div></div>
                <button class="dpad-btn" id="btn-fata" data-cmd="fata">
                    &#9650;<span class="key-hint">W</span>
                </button>
                <div></div>
                <button class="dpad-btn" id="btn-stanga" data-cmd="stanga">
                    &#9664;<span class="key-hint">A</span>
                </button>
                <button class="dpad-btn stop" id="btn-stop">STOP</button>
                <button class="dpad-btn" id="btn-dreapta" data-cmd="dreapta">
                    &#9654;<span class="key-hint">D</span>
                </button>
                <div></div>
                <button class="dpad-btn" id="btn-spate" data-cmd="spate">
                    &#9660;<span class="key-hint">S</span>
                </button>
                <div></div>
            </div>
            <div class="dpad-hint">TINE APASAT PENTRU MISCARE &bull; W A S D / SAGETI &bull; SPACE = STOP</div>
        </div>

        <div class="panel-card">
            <div class="panel-title">Status Operatiune</div>
            <div class="robot-screen">
                <div class="screen-header">
                    <span>ROBOT.SYS</span>
                    <span id="screenClock">--:--:--</span>
                </div>
                <div class="step-row" data-step="0">
                    <span class="step-icon">&#9675;</span> CAUTARE FISURA
                </div>
                <div class="step-row" data-step="1">
                    <span class="step-icon">&#9675;</span> GASIRE FISURA
                </div>
                <div class="step-row" data-step="2">
                    <span class="step-icon">&#9675;</span> START FREZA + ASPIRATOR
                </div>
                <div class="step-row" data-step="3">
                    <span class="step-icon">&#9675;</span> APLICARE STRAT
                </div>
                <div class="step-row" data-step="4">
                    <span class="step-icon">&#9675;</span> TERMINARE
                </div>
                <div class="screen-header" style="border: none; margin-top: 16px; padding: 0;">
                    <span>&gt; standby<span class="screen-cursor">_</span></span>
                </div>
            </div>
        </div>

    </div>

</main>

<script>
const CAM1_URL = '';
const CAM2_URL = '';

function initCam(num, url) {
    if (!url) return;
    const img = document.getElementById('cam' + num + 'Stream');
    img.onload = () => {
        img.style.display = 'block';
        document.getElementById('cam' + num + 'Offline').style.display = 'none';
        document.getElementById('cam' + num + 'Dot').classList.add('online');
    };
    img.onerror = () => {
        img.style.display = 'none';
        document.getElementById('cam' + num + 'Offline').style.display = 'flex';
        document.getElementById('cam' + num + 'Dot').classList.remove('online');
    };
    img.src = url;
}
initCam(1, CAM1_URL);
initCam(2, CAM2_URL);

const SEND_INTERVAL_MS = 100;
let holdTimer = null;
let activeCmd = null;

function sendCommand(cmd) {
    fetch('api_robot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({command: cmd})
    }).catch(() => {});
}

function startMove(cmd) {
    if (activeCmd === cmd) return;
    stopMove(false);
    activeCmd = cmd;
    const btn = document.getElementById('btn-' + cmd);
    if (btn) btn.classList.add('pressed');
    sendCommand(cmd);
    holdTimer = setInterval(() => sendCommand(cmd), SEND_INTERVAL_MS);
}

function stopMove(sendStop = true) {
    if (holdTimer) { clearInterval(holdTimer); holdTimer = null; }
    if (activeCmd) {
        const btn = document.getElementById('btn-' + activeCmd);
        if (btn) btn.classList.remove('pressed');
        activeCmd = null;
        if (sendStop) sendCommand('stop');
    }
}

document.querySelectorAll('.dpad-btn[data-cmd]').forEach(btn => {
    const cmd = btn.dataset.cmd;
    btn.addEventListener('pointerdown', e => { e.preventDefault(); startMove(cmd); });
    btn.addEventListener('pointerup',    () => stopMove());
    btn.addEventListener('pointerleave', () => { if (activeCmd === cmd) stopMove(); });
    btn.addEventListener('contextmenu',  e => e.preventDefault());
});
document.getElementById('btn-stop').addEventListener('pointerdown', e => {
    e.preventDefault();
    stopMove(false);
    sendCommand('stop');
    const b = document.getElementById('btn-stop');
    b.classList.add('pressed');
    setTimeout(() => b.classList.remove('pressed'), 180);
});

const KEYMAP = {
    'w': 'fata', 'arrowup': 'fata',
    's': 'spate', 'arrowdown': 'spate',
    'a': 'stanga', 'arrowleft': 'stanga',
    'd': 'dreapta', 'arrowright': 'dreapta'
};
document.addEventListener('keydown', e => {
    if (e.repeat) return;
    if (e.key === ' ') { e.preventDefault(); stopMove(false); sendCommand('stop'); return; }
    const cmd = KEYMAP[e.key.toLowerCase()];
    if (cmd) { e.preventDefault(); startMove(cmd); }
});
document.addEventListener('keyup', e => {
    const cmd = KEYMAP[e.key.toLowerCase()];
    if (cmd && activeCmd === cmd) stopMove();
});
window.addEventListener('blur', () => stopMove());

async function checkRobot() {
    const dot = document.getElementById('robotDot');
    const txt = document.getElementById('robotStatus');
    try {
        const res = await fetch('api_robot.php');
        const data = await res.json();
        if (data.connected) {
            dot.classList.add('online');
            txt.textContent = 'Robot conectat';
        } else {
            dot.classList.remove('online');
            txt.textContent = data.error || 'Robot neconectat';
        }
    } catch {
        dot.classList.remove('online');
        txt.textContent = 'Eroare server';
    }
}
checkRobot();
setInterval(checkRobot, 4000);

function setRobotStep(n) {
    document.querySelectorAll('.step-row').forEach(row => {
        const idx = parseInt(row.dataset.step);
        row.classList.remove('active', 'done');
        const icon = row.querySelector('.step-icon');
        if (idx < n) {
            row.classList.add('done');
            icon.innerHTML = '&#10003;';
        } else if (idx === n) {
            row.classList.add('active');
            icon.innerHTML = '&#9679;';
        } else {
            icon.innerHTML = '&#9675;';
        }
    });
}

setInterval(() => {
    document.getElementById('screenClock').textContent =
        new Date().toLocaleTimeString('ro-RO');
}, 1000);

document.addEventListener('mousemove', e => {
    const g = document.getElementById('cursorGlow');
    if (g) { g.style.left = e.clientX + 'px'; g.style.top = e.clientY + 'px'; }
});
</script>

</body>
</html>
