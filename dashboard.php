<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

require 'db.php';
$db = getDB();

$all_sensors = $db->query("SELECT * FROM sensors ORDER BY id")->fetchAll();

$allowed_ids = [1, 2, 4, 6, 7];
$sensors = array_filter($all_sensors, fn($s) => in_array((int)$s['id'], $allowed_ids));
$sensors = array_values($sensors);

$stops = $db->query("SELECT sensor_id FROM alerts WHERE status='activ'")->fetchAll(PDO::FETCH_COLUMN);
$urgs  = $db->query("SELECT sensor_id, mesaj FROM urgente_mesaje WHERE status='activ' ORDER BY id DESC")->fetchAll();
$urg_by_sensor = [];
foreach ($urgs as $u) {
    if (!isset($urg_by_sensor[$u['sensor_id']])) $urg_by_sensor[$u['sensor_id']] = $u['mesaj'];
}

function cardClass(int $sid, array $stops, array $urgBySensor): string {
    if (in_array($sid, $stops)) return 'border-red';
    if (isset($urgBySensor[$sid])) return 'border-yellow';
    return 'border-green';
}

function dotColor(int $sid, array $stops, array $urgBy): string {
    if (in_array($sid, $stops)) return '#ff3b5c';
    if (isset($urgBy[$sid])) return '#ffaa00';
    return '#00d97e';
}

function dotMsg(int $sid, array $stops, array $urgBy): string {
    if (in_array($sid, $stops)) return 'Stop trafic activ';
    if (isset($urgBy[$sid])) return htmlspecialchars($urgBy[$sid]);
    return 'Funcționare normală';
}

$history = $db->query("
    SELECT 'stop_trafic' AS tip, a.sensor_id, s.name AS sensor_name, '' AS mesaj, a.status, a.created_at
    FROM alerts a JOIN sensors s ON a.sensor_id=s.id
    UNION ALL
    SELECT 'urgenta' AS tip, u.sensor_id, s.name AS sensor_name, u.mesaj, u.status, u.created_at
    FROM urgente_mesaje u JOIN sensors s ON u.sensor_id=s.id
    ORDER BY created_at DESC LIMIT 8
")->fetchAll();

$dot_map = [
    1 => ['cx' => 200, 'cy' => 155, 'label' => 'Pilon Stânga'],
    2 => ['cx' => 350, 'cy' => 128, 'label' => 'Pod Stânga'],
    4 => ['cx' => 500, 'cy' => 128, 'label' => 'Pod Mijloc'],
    7 => ['cx' => 650, 'cy' => 128, 'label' => 'Pod Dreapta'],
    6 => ['cx' => 720, 'cy' => 155, 'label' => 'Pilon Dreapta'],
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BRIDGEGUARD</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .bridge-svg-wrap {
            position: relative;
            background: linear-gradient(180deg, var(--clr-bg-alt) 0%, var(--clr-bg) 100%);
            border: var(--border);
            border-radius: 20px;
            padding: 24px;
            overflow: hidden;
        }
        .bridge-svg-wrap::before {
            content: '';
            position: absolute;
            top: 0; left: 50%;
            transform: translateX(-50%);
            width: 50%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--clr-accent), transparent);
            opacity: 0.3;
        }
        .bridge-svg-wrap svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .svg-dot {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .svg-dot:hover {
            filter: brightness(1.4);
        }
        .svg-dot-ring {
            animation: svg-ring-pulse 2.5s ease-in-out infinite;
        }
        @keyframes svg-ring-pulse {
            0%, 100% { r: 16; opacity: 0.15; }
            50%      { r: 22; opacity: 0; }
        }
        .svg-dot-alert .svg-dot-ring {
            animation: svg-ring-alert 1.5s ease-in-out infinite;
        }
        @keyframes svg-ring-alert {
            0%, 100% { r: 16; opacity: 0.3; }
            50%      { r: 24; opacity: 0; }
        }

        .bridge-popup {
            position: absolute;
            background: var(--clr-surface);
            border: 1px solid var(--clr-border-h);
            border-radius: 10px;
            padding: 12px 16px;
            z-index: 30;
            min-width: 150px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5), var(--shadow-glow);
            pointer-events: none;
            display: none;
            font-family: var(--font-mono);
        }
        .bridge-popup .pop-name {
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 4px;
        }
        .bridge-popup .pop-loc {
            font-size: 0.65rem;
            color: var(--clr-subtle);
            margin-bottom: 6px;
        }
        .bridge-popup .pop-status {
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .gds-card {
            grid-column: span 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            overflow: hidden;
        }
        .gds-half {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            gap: 10px;
        }
        .gds-half:first-child {
            border-right: 1px solid var(--clr-border);
        }
        .gds-half-title {
            font-family: var(--font-mono);
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--clr-muted);
        }
        .greutate-value {
            font-family: var(--font-mono);
            font-size: 2rem;
            font-weight: 700;
            color: var(--clr-accent);
            transition: all 0.3s ease;
        }
        .greutate-value.alerta {
            color: #ff1a1a;
            text-shadow: 0 0 20px rgba(255,26,26,0.5);
        }
        .greutate-unit {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-muted);
        }
        .semafor-display {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .semafor-body {
            background: linear-gradient(180deg, #1a1a2e, #0d0d1a);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .semafor-light {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .semafor-light.rosu { background: #330000; }
        .semafor-light.rosu.activ {
            background: #ff1a1a;
            box-shadow: 0 0 20px #ff1a1a, 0 0 40px rgba(255,26,26,0.4);
        }
        .semafor-light.galben { background: #332b00; }
        .semafor-light.galben.activ {
            background: #ffcc00;
            box-shadow: 0 0 20px #ffcc00, 0 0 40px rgba(255,204,0,0.4);
        }
        .semafor-light.verde { background: #003300; }
        .semafor-light.verde.activ {
            background: #00ff44;
            box-shadow: 0 0 20px #00ff44, 0 0 40px rgba(0,255,68,0.4);
        }
        .semafor-status {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            text-align: center;
            color: var(--clr-muted);
            letter-spacing: 0.05em;
        }
        .semafor-status.detectat {
            color: #ff1a1a;
            font-weight: bold;
        }
        .gds-mesaj {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            font-weight: 700;
            color: #ff1a1a;
            text-align: center;
            min-height: 1.2em;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gds-mesaj.activ {
            opacity: 1;
            animation: pulse-mesaj 0.5s ease infinite alternate;
        }
        @keyframes pulse-mesaj {
            from { opacity: 0.7; }
            to { opacity: 1; text-shadow: 0 0 15px rgba(255,26,26,0.6); }
        }
        @media (max-width: 600px) {
            .gds-card { grid-column: span 1; grid-template-columns: 1fr; }
            .gds-half:first-child { border-right: none; border-bottom: 1px solid var(--clr-border); }
        }
        @media (max-width: 900px) {
            .bridge-schema [style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }
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

    <div class="section-title">Senzori activi</div>
    <div class="sensor-grid">
        <?php foreach ($sensors as $s):
            $sid = (int)$s['id'];
            $cc  = cardClass($sid, $stops, $urg_by_sensor);
            $isGyro    = ($sid === 6);
            $isBobinaj = ($sid === 7);
        ?>
            <div class="sensor-card <?= $cc ?>" onclick="location.href='detail.php?sensor_id=<?= $s['id'] ?>'">
                <?php if ($isGyro): ?>
                    <div class="chart-wrap" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:8px 4px;">
                        <div style="font-size:0.6rem;color:var(--clr-subtle);letter-spacing:.08em;text-transform:uppercase;">Față-Spate</div>
                        <div id="gyro-fs" style="font-size:1.4rem;font-weight:600;color:#00d97e;font-family:var(--font-mono);">—°</div>
                        <div style="font-size:0.6rem;color:var(--clr-subtle);letter-spacing:.08em;text-transform:uppercase;margin-top:4px;">Stânga-Dreapta</div>
                        <div id="gyro-sd" style="font-size:1.4rem;font-weight:600;color:#34d399;font-family:var(--font-mono);">—°</div>
                        <div id="gyro-status" style="font-size:0.55rem;margin-top:6px;color:var(--clr-muted);">Așteptare Pico...</div>
                    </div>
                <?php elseif ($isBobinaj): ?>
                    <div class="chart-wrap" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:8px 4px;">
                        <div style="font-size:0.6rem;color:var(--clr-subtle);letter-spacing:.08em;text-transform:uppercase;">Stare cablu</div>
                        <div id="bobinaj-icon" style="font-size:2rem;line-height:1;margin:4px 0;">—</div>
                        <div id="bobinaj-text" style="font-size:1rem;font-weight:600;color:#00d97e;font-family:var(--font-mono);">—</div>
                        <div id="bobinaj-status" style="font-size:0.55rem;margin-top:6px;color:var(--clr-muted);">Așteptare Pico...</div>
                    </div>
                <?php else: ?>
                    <div class="chart-wrap">
                        <canvas id="c<?= $s['id'] ?>"></canvas>
                    </div>
                <?php endif; ?>
                <div class="card-label">
                    <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['unit']) ?>)
                </div>
            </div>
        <?php endforeach; ?>

        <div class="sensor-card gds-card" id="gds-card">
            <div class="gds-half">
                <div class="gds-half-title">Cantar (HX711)</div>
                <div class="greutate-value" id="greutate-value">0.000</div>
                <div class="greutate-unit">kg</div>
            </div>
            <div class="gds-half">
                <div class="gds-half-title">Semafor</div>
                <div class="semafor-display">
                    <div class="semafor-body">
                        <div class="semafor-light rosu" id="sem-rosu"></div>
                        <div class="semafor-light galben" id="sem-galben"></div>
                        <div class="semafor-light verde" id="sem-verde"></div>
                    </div>
                </div>
                <div class="semafor-status" id="semafor-status">Se conecteaza...</div>
                <div class="gds-mesaj" id="gds-mesaj"></div>
            </div>
        </div>
    </div>

    <div class="bridge-schema">
        <div class="bridge-schema-title">
            Monitorizare Pod
            <span style="font-size:0.7rem;color:var(--clr-muted);font-family:var(--font-mono);">click pe senzor</span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

        <div class="bridge-svg-wrap" id="bridge-wrap" style="position:relative;">
            <div style="position:absolute;top:12px;left:16px;z-index:20;font-family:var(--font-mono);font-size:0.7rem;letter-spacing:0.12em;text-transform:uppercase;color:var(--clr-accent);background:var(--clr-accent-dim);border:1px solid rgba(0,217,126,0.15);padding:5px 14px;border-radius:999px;">POD INEU</div>
            <div style="position:absolute;top:12px;right:16px;z-index:20;font-family:var(--font-mono);font-size:0.6rem;color:var(--clr-muted);letter-spacing:0.08em;">VEDERE LATERALĂ</div>
            <svg viewBox="0 0 900 320" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="pillar-g" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#1a3a24"/>
                        <stop offset="40%" stop-color="#264a30"/>
                        <stop offset="100%" stop-color="#153520"/>
                    </linearGradient>
                    <linearGradient id="deck-g" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#2a4f30"/>
                        <stop offset="100%" stop-color="#1a3a24"/>
                    </linearGradient>
                    <linearGradient id="road-g" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3a5f3a"/>
                        <stop offset="100%" stop-color="#2a4f30"/>
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="blur"/>
                        <feMerge>
                            <feMergeNode in="blur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    <filter id="shadow-f">
                        <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#000" flood-opacity="0.3"/>
                    </filter>
                </defs>

                <ellipse cx="450" cy="160" rx="350" ry="100" fill="rgba(0, 217, 126, 0.015)"/>

                <rect x="0" y="240" width="900" height="80" fill="#061a0e" rx="0"/>
                <line x1="60" y1="260" x2="840" y2="260" stroke="rgba(0, 217, 126, 0.05)" stroke-width="1"/>
                <line x1="80" y1="280" x2="820" y2="280" stroke="rgba(0, 217, 126, 0.03)" stroke-width="1"/>

                <path d="M 175 140 L 185 140 L 195 300 L 165 300 Z" fill="url(#pillar-g)" filter="url(#shadow-f)"/>
                <path d="M 185 140 L 225 140 L 235 300 L 195 300 Z" fill="#1e4030" filter="url(#shadow-f)"/>
                <rect x="158" y="295" width="84" height="8" rx="2" fill="#163a20"/>

                <path d="M 685 140 L 695 140 L 705 300 L 675 300 Z" fill="url(#pillar-g)" filter="url(#shadow-f)"/>
                <path d="M 695 140 L 735 140 L 745 300 L 705 300 Z" fill="#1e4030" filter="url(#shadow-f)"/>
                <rect x="668" y="295" width="84" height="8" rx="2" fill="#163a20"/>

                <rect x="60" y="120" width="780" height="28" rx="3" fill="url(#deck-g)" filter="url(#shadow-f)"/>

                <rect x="60" y="120" width="780" height="12" rx="3" fill="url(#road-g)"/>

                <line x1="120" y1="126" x2="180" y2="126" stroke="#00d97e" stroke-width="1.5" opacity="0.25" stroke-dasharray="12 8"/>
                <line x1="220" y1="126" x2="340" y2="126" stroke="#00d97e" stroke-width="1.5" opacity="0.25" stroke-dasharray="12 8"/>
                <line x1="380" y1="126" x2="520" y2="126" stroke="#00d97e" stroke-width="1.5" opacity="0.25" stroke-dasharray="12 8"/>
                <line x1="560" y1="126" x2="680" y2="126" stroke="#00d97e" stroke-width="1.5" opacity="0.25" stroke-dasharray="12 8"/>
                <line x1="720" y1="126" x2="780" y2="126" stroke="#00d97e" stroke-width="1.5" opacity="0.25" stroke-dasharray="12 8"/>

                <rect x="60" y="108" width="780" height="3" rx="1" fill="#1a3a24" opacity="0.8"/>

                <?php for ($i = 80; $i <= 820; $i += 40): ?>
                <line x1="<?= $i ?>" y1="108" x2="<?= $i ?>" y2="120" stroke="#1e4030" stroke-width="2" opacity="0.5"/>
                <?php endfor; ?>

                <line x1="200" y1="80" x2="200" y2="110" stroke="rgba(0, 217, 126, 0.3)" stroke-width="1.5"/>
                <line x1="710" y1="80" x2="710" y2="110" stroke="rgba(0, 217, 126, 0.3)" stroke-width="1.5"/>

                <path d="M 200 80 Q 455 40 710 80" stroke="rgba(0, 217, 126, 0.2)" stroke-width="2" fill="none"/>

                <line x1="300" y1="66" x2="300" y2="110" stroke="rgba(0, 217, 126, 0.1)" stroke-width="1"/>
                <line x1="400" y1="55" x2="400" y2="110" stroke="rgba(0, 217, 126, 0.1)" stroke-width="1"/>
                <line x1="500" y1="52" x2="500" y2="110" stroke="rgba(0, 217, 126, 0.1)" stroke-width="1"/>
                <line x1="600" y1="58" x2="600" y2="110" stroke="rgba(0, 217, 126, 0.1)" stroke-width="1"/>

                <rect x="175" y="75" width="50" height="8" rx="3" fill="#264a30"/>
                <rect x="685" y="75" width="50" height="8" rx="3" fill="#264a30"/>

                <?php foreach ($sensors as $s):
                    $sid = (int)$s['id'];
                    if (!isset($dot_map[$sid])) continue;
                    $dm  = $dot_map[$sid];
                    $col = dotColor($sid, $stops, $urg_by_sensor);
                    $msg = dotMsg($sid, $stops, $urg_by_sensor);
                    $isAlert = ($col !== '#00d97e');
                ?>
                <g class="svg-dot <?= $isAlert ? 'svg-dot-alert' : '' ?>"
                   data-sid="<?= $sid ?>"
                   data-name="<?= htmlspecialchars($s['name']) ?>"
                   data-loc="<?= $dm['label'] ?>"
                   data-msg="<?= $msg ?>"
                   data-color="<?= $col ?>"
                   onclick="toggleSvgPopup(event, <?= $sid ?>)">
                    <circle class="svg-dot-ring" cx="<?= $dm['cx'] ?>" cy="<?= $dm['cy'] ?>" r="16" fill="<?= $col ?>" opacity="0.15"/>
                    <circle cx="<?= $dm['cx'] ?>" cy="<?= $dm['cy'] ?>" r="10" fill="<?= $col ?>" opacity="0.2" filter="url(#glow)"/>
                    <circle cx="<?= $dm['cx'] ?>" cy="<?= $dm['cy'] ?>" r="6" fill="<?= $col ?>" stroke="#fff" stroke-width="1.5" stroke-opacity="0.5"/>
                    <circle cx="<?= $dm['cx'] ?>" cy="<?= $dm['cy'] ?>" r="2" fill="#fff" opacity="0.7"/>
                </g>
                <?php endforeach; ?>
            </svg>

            <?php foreach ($sensors as $s):
                $sid = (int)$s['id'];
                if (!isset($dot_map[$sid])) continue;
                $dm  = $dot_map[$sid];
                $col = dotColor($sid, $stops, $urg_by_sensor);
                $msg = dotMsg($sid, $stops, $urg_by_sensor);
            ?>
            <div class="bridge-popup" id="popup-<?= $sid ?>">
                <div class="pop-name" style="color:<?= $col ?>"><?= htmlspecialchars($s['name']) ?></div>
                <div class="pop-loc"><?= $dm['label'] ?></div>
                <div class="pop-status" style="color:var(--clr-text-body)"><?= $msg ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="bridge-svg-wrap" style="position:relative;">
            <div style="position:absolute;top:12px;left:16px;z-index:20;font-family:var(--font-mono);font-size:0.7rem;letter-spacing:0.12em;text-transform:uppercase;color:var(--clr-accent);background:var(--clr-accent-dim);border:1px solid rgba(0,217,126,0.15);padding:5px 14px;border-radius:999px;">POD INEU</div>
            <div style="position:absolute;top:12px;right:16px;z-index:20;font-family:var(--font-mono);font-size:0.6rem;color:var(--clr-muted);letter-spacing:0.08em;">VEDERE DE SUS</div>
            <svg viewBox="0 0 900 320" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="road-top-g" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#2a4f30"/>
                        <stop offset="50%" stop-color="#3a5f3a"/>
                        <stop offset="100%" stop-color="#2a4f30"/>
                    </linearGradient>
                    <linearGradient id="water-top-g" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#061a0e"/>
                        <stop offset="100%" stop-color="#0a2015"/>
                    </linearGradient>
                    <filter id="glow-top">
                        <feGaussianBlur stdDeviation="2" result="blur"/>
                        <feMerge>
                            <feMergeNode in="blur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>

                <ellipse cx="450" cy="160" rx="350" ry="120" fill="rgba(0, 217, 126, 0.01)"/>

                <rect x="0" y="0" width="900" height="320" fill="url(#water-top-g)" rx="0"/>

                <ellipse cx="250" cy="90" rx="120" ry="8" fill="none" stroke="rgba(0, 217, 126, 0.04)" stroke-width="1"/>
                <ellipse cx="650" cy="250" rx="100" ry="6" fill="none" stroke="rgba(0, 217, 126, 0.03)" stroke-width="1"/>
                <ellipse cx="400" cy="270" rx="140" ry="7" fill="none" stroke="rgba(0, 217, 126, 0.03)" stroke-width="1"/>

                <rect x="0" y="120" width="80" height="80" fill="#1a3a24" rx="4"/>
                <rect x="60" y="130" width="30" height="60" fill="#1e4030" rx="2"/>

                <rect x="820" y="120" width="80" height="80" fill="#1a3a24" rx="4"/>
                <rect x="810" y="130" width="30" height="60" fill="#1e4030" rx="2"/>

                <rect x="80" y="130" width="740" height="60" rx="3" fill="url(#road-top-g)" stroke="rgba(0, 217, 126, 0.1)" stroke-width="1"/>

                <rect x="80" y="128" width="740" height="3" rx="1" fill="#264a30"/>
                <rect x="80" y="189" width="740" height="3" rx="1" fill="#264a30"/>

                <?php for ($i = 100; $i <= 800; $i += 35): ?>
                <rect x="<?= $i ?>" y="126" width="2" height="6" fill="#1e4030" opacity="0.6"/>
                <rect x="<?= $i ?>" y="188" width="2" height="6" fill="#1e4030" opacity="0.6"/>
                <?php endfor; ?>

                <line x1="100" y1="160" x2="180" y2="160" stroke="#00d97e" stroke-width="2" opacity="0.2" stroke-dasharray="14 10"/>
                <line x1="210" y1="160" x2="340" y2="160" stroke="#00d97e" stroke-width="2" opacity="0.2" stroke-dasharray="14 10"/>
                <line x1="370" y1="160" x2="530" y2="160" stroke="#00d97e" stroke-width="2" opacity="0.2" stroke-dasharray="14 10"/>
                <line x1="560" y1="160" x2="690" y2="160" stroke="#00d97e" stroke-width="2" opacity="0.2" stroke-dasharray="14 10"/>
                <line x1="720" y1="160" x2="800" y2="160" stroke="#00d97e" stroke-width="2" opacity="0.2" stroke-dasharray="14 10"/>

                <line x1="80" y1="138" x2="820" y2="138" stroke="rgba(0, 217, 126, 0.08)" stroke-width="1"/>
                <line x1="80" y1="182" x2="820" y2="182" stroke="rgba(0, 217, 126, 0.08)" stroke-width="1"/>

                <rect x="170" y="125" width="40" height="70" rx="4" fill="#264a30" stroke="rgba(0, 217, 126, 0.15)" stroke-width="1"/>
                <rect x="175" y="130" width="30" height="60" rx="2" fill="#1a3a24"/>

                <rect x="690" y="125" width="40" height="70" rx="4" fill="#264a30" stroke="rgba(0, 217, 126, 0.15)" stroke-width="1"/>
                <rect x="695" y="130" width="30" height="60" rx="2" fill="#1a3a24"/>

                <line x1="190" y1="132" x2="710" y2="132" stroke="rgba(0, 217, 126, 0.12)" stroke-width="1"/>
                <line x1="190" y1="188" x2="710" y2="188" stroke="rgba(0, 217, 126, 0.12)" stroke-width="1"/>

                <line x1="195" y1="135" x2="300" y2="138" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>
                <line x1="195" y1="135" x2="400" y2="138" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>
                <line x1="195" y1="135" x2="500" y2="138" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>
                <line x1="710" y1="135" x2="600" y2="138" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>
                <line x1="710" y1="135" x2="500" y2="138" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>

                <line x1="195" y1="185" x2="300" y2="182" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>
                <line x1="195" y1="185" x2="400" y2="182" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>
                <line x1="710" y1="185" x2="600" y2="182" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>
                <line x1="710" y1="185" x2="500" y2="182" stroke="rgba(0, 217, 126, 0.06)" stroke-width="0.8"/>

                <?php
                $dot_map_top = [
                    1 => ['cx' => 190, 'cy' => 160],
                    2 => ['cx' => 320, 'cy' => 150],
                    4 => ['cx' => 450, 'cy' => 160],
                    7 => ['cx' => 600, 'cy' => 170],
                    6 => ['cx' => 710, 'cy' => 160],
                ];
                foreach ($sensors as $s):
                    $sid = (int)$s['id'];
                    if (!isset($dot_map_top[$sid])) continue;
                    $dtm = $dot_map_top[$sid];
                    $col = dotColor($sid, $stops, $urg_by_sensor);
                ?>
                <g class="svg-dot <?= (in_array($sid, $stops) || isset($urg_by_sensor[$sid])) ? 'svg-dot-alert' : '' ?>">
                    <circle class="svg-dot-ring" cx="<?= $dtm['cx'] ?>" cy="<?= $dtm['cy'] ?>" r="14" fill="<?= $col ?>" opacity="0.15"/>
                    <circle cx="<?= $dtm['cx'] ?>" cy="<?= $dtm['cy'] ?>" r="8" fill="<?= $col ?>" opacity="0.2" filter="url(#glow-top)"/>
                    <circle cx="<?= $dtm['cx'] ?>" cy="<?= $dtm['cy'] ?>" r="5" fill="<?= $col ?>" stroke="#fff" stroke-width="1.2" stroke-opacity="0.5"/>
                    <circle cx="<?= $dtm['cx'] ?>" cy="<?= $dtm['cy'] ?>" r="1.5" fill="#fff" opacity="0.7"/>
                </g>
                <?php endforeach; ?>

                <text x="50" y="165" font-family="'JetBrains Mono', monospace" font-size="8" fill="rgba(0, 217, 126, 0.3)" text-anchor="middle" letter-spacing="0.1em">MAL</text>
                <text x="855" y="165" font-family="'JetBrains Mono', monospace" font-size="8" fill="rgba(0, 217, 126, 0.3)" text-anchor="middle" letter-spacing="0.1em">MAL</text>

                <polygon points="430,145 445,140 445,150" fill="rgba(0, 217, 126, 0.15)"/>
                <polygon points="470,175 455,170 455,180" fill="rgba(0, 217, 126, 0.15)"/>

                <line x1="350" y1="290" x2="550" y2="290" stroke="rgba(0, 217, 126, 0.15)" stroke-width="1"/>
                <line x1="350" y1="286" x2="350" y2="294" stroke="rgba(0, 217, 126, 0.15)" stroke-width="1"/>
                <line x1="550" y1="286" x2="550" y2="294" stroke="rgba(0, 217, 126, 0.15)" stroke-width="1"/>
                <text x="450" y="304" font-family="'JetBrains Mono', monospace" font-size="7" fill="rgba(0, 217, 126, 0.2)" text-anchor="middle">~ lungime pod</text>
            </svg>
        </div>

        </div><!-- închide grid -->
    </div>

    <div class="section-title">Istoric Alerte</div>
    <div class="alert-history-list" style="margin-bottom:48px;">
        <?php if (empty($history)): ?>
            <div style="text-align:center;padding:32px;color:var(--clr-muted);font-family:var(--font-mono);font-size:0.8rem;">
                Nicio alertă înregistrată
            </div>
        <?php else: foreach ($history as $h): ?>
            <div class="alert-history-item type-<?= $h['tip'] === 'stop_trafic' ? 'stop' : 'urgenta' ?>">
                <div class="alert-history-icon <?= $h['tip'] === 'stop_trafic' ? 'icon-stop' : 'icon-urgenta' ?>">
                    <?= $h['tip'] === 'stop_trafic' ? '🛑' : '📢' ?>
                </div>
                <div class="alert-history-content">
                    <div class="alert-history-title">
                        <?= $h['tip'] === 'stop_trafic' ? 'Stop Trafic' : 'Urgență' ?>
                        — <?= htmlspecialchars($h['sensor_name']) ?>
                    </div>
                    <div class="alert-history-meta">
                        <?= htmlspecialchars($h['mesaj'] ?: 'Alertă sistem') ?>
                    </div>
                </div>
                <div class="alert-history-status">
                    <span class="badge <?= $h['status'] === 'activ' ? 'badge-red' : 'badge-green' ?>">
                        <?= strtoupper($h['status']) ?>
                    </span>
                </div>
                <div class="alert-history-time">
                    <?= date('d.m.Y', strtotime($h['created_at'])) ?><br>
                    <?= date('H:i', strtotime($h['created_at'])) ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="section-title">Cameră & Detectare Fisuri</div>
    <div style="display:flex;justify-content:center;gap:16px;margin-bottom:48px;">
        <a href="camera.php" class="btn-primary" style="font-size:0.9rem;padding:14px 32px;">
            DESCHIDE CAMERĂ LIVE
        </a>
        <a href="galerie.php" class="btn-secondary" style="font-size:0.9rem;padding:14px 32px;">
            POZE COLECTATE
        </a>
    </div>

</main>

<script>
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top = e.clientY + 'px';
});

let openPopup = null;

function toggleSvgPopup(e, sid) {
    e.stopPropagation();
    const popup = document.getElementById('popup-' + sid);
    const wrap  = document.getElementById('bridge-wrap');

    if (openPopup && openPopup !== popup) {
        openPopup.style.display = 'none';
    }

    if (popup.style.display === 'block') {
        popup.style.display = 'none';
        openPopup = null;
        return;
    }

    const svg = wrap.querySelector('svg');
    const svgRect = svg.getBoundingClientRect();
    const wrapRect = wrap.getBoundingClientRect();

    const dotG = e.currentTarget;
    const circle = dotG.querySelector('circle:nth-child(3)');
    const cx = parseFloat(circle.getAttribute('cx'));
    const cy = parseFloat(circle.getAttribute('cy'));

    const scaleX = svgRect.width / 900;
    const scaleY = svgRect.height / 320;
    let left = svgRect.left - wrapRect.left + cx * scaleX + 20;
    let top  = svgRect.top  - wrapRect.top  + cy * scaleY - 10;

    popup.style.display = 'block';
    popup.style.left = left + 'px';
    popup.style.top  = top + 'px';
    openPopup = popup;
}

document.addEventListener('click', () => {
    if (openPopup) { openPopup.style.display = 'none'; openPopup = null; }
});

const sensors = <?= json_encode(array_values($sensors)) ?>;
const charts  = {};

Chart.defaults.color = '#5a6a80';
Chart.defaults.borderColor = 'rgba(60, 180, 100, 0.04)';
Chart.defaults.font.family = "'JetBrains Mono', monospace";

sensors.forEach(s => {
    if (parseInt(s.id) === 7) {
        setInterval(() => {
            fetch(`api_sensor.php?action=value&id=7`)
                .then(r => r.json())
                .then(d => {
                    const icon   = document.getElementById('bobinaj-icon');
                    const text   = document.getElementById('bobinaj-text');
                    const status = document.getElementById('bobinaj-status');
                    if (!icon) return;

                    if (d.online) {
                        if (d.value === 1) {
                            icon.textContent = '🟢';
                            text.textContent = 'INTACT';
                            text.style.color = '#00d97e';
                        } else {
                            icon.textContent = '🔴';
                            text.textContent = 'RUPT';
                            text.style.color = '#ff3b5c';
                        }
                        status.textContent = 'Pico Online';
                        status.style.color = '#00d97e';
                    } else {
                        icon.textContent   = '—';
                        text.textContent   = '—';
                        status.textContent = 'Pico Offline';
                        status.style.color = '#ff3b5c';
                    }
                })
                .catch(() => {
                    const s2 = document.getElementById('bobinaj-status');
                    if (s2) { s2.textContent = 'Eroare'; s2.style.color = '#ff3b5c'; }
                });
        }, 500);
        return;
    }

    if (parseInt(s.id) === 6) {
        setInterval(() => {
            fetch(`api_sensor.php?action=value&id=6`)
                .then(r => r.json())
                .then(d => {
                    const elFS = document.getElementById('gyro-fs');
                    const elSD = document.getElementById('gyro-sd');
                    const elSt = document.getElementById('gyro-status');
                    if (!elFS) return;

                    if (d.online) {
                        const fs = d.fata_spate;
                        const sd = d.stanga_dreapta;
                        elFS.textContent = (fs >= 0 ? '+' : '') + fs + '°';
                        elSD.textContent = (sd >= 0 ? '+' : '') + sd + '°';
                        const colorFS = Math.abs(fs) > 15 ? '#ff3b5c' : Math.abs(fs) > 10 ? '#ffaa00' : '#00d97e';
                        const colorSD = Math.abs(sd) > 15 ? '#ff3b5c' : Math.abs(sd) > 10 ? '#ffaa00' : '#34d399';
                        elFS.style.color = colorFS;
                        elSD.style.color = colorSD;
                        elSt.textContent = 'Pico Online';
                        elSt.style.color = '#00d97e';
                    } else {
                        elFS.textContent = '—°';
                        elSD.textContent = '—°';
                        elSt.textContent = 'Pico Offline';
                        elSt.style.color = '#ff3b5c';
                    }
                })
                .catch(() => {
                    const elSt = document.getElementById('gyro-status');
                    if (elSt) { elSt.textContent = 'Eroare'; elSt.style.color = '#ff3b5c'; }
                });
        }, 500);
        return;
    }

    const ctx = document.getElementById('c' + s.id);
    if (!ctx) return;

    charts[s.id] = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: Array(25).fill(''),
            datasets: [{
                data: Array(25).fill(null),
                borderColor: '#00d97e',
                borderWidth: 1.5,
                pointRadius: 0,
                tension: 0.45,
                fill: true,
                backgroundColor: 'rgba(0, 217, 126, 0.04)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false },
                y: { display: true, grid: { color: 'rgba(60, 180, 100, 0.04)' }, ticks: { font: { size: 9 } } }
            },
            animation: { duration: 250 }
        }
    });

    setInterval(() => {
        fetch(`api_sensor.php?action=value&id=${s.id}`)
            .then(r => r.json())
            .then(d => {
                const ds = charts[s.id].data.datasets[0];
                const val = (d.value !== null && d.value !== undefined) ? d.value : null;
                ds.data.push(val);
                if (ds.data.length > 25) ds.data.shift();
                charts[s.id].update('none');
            });
    }, 1200);
});

document.querySelectorAll('.btn-primary, .btn-alert, .sensor-card').forEach(el => {
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

function updateGDS() {
    fetch('api_semafor.php')
        .then(r => r.json())
        .then(data => {
            const rosu = document.getElementById('sem-rosu');
            const galben = document.getElementById('sem-galben');
            const verde = document.getElementById('sem-verde');
            const status = document.getElementById('semafor-status');
            const greutateEl = document.getElementById('greutate-value');
            const mesajEl = document.getElementById('gds-mesaj');

            const g = data.greutate || 0;
            greutateEl.textContent = g.toFixed(3);
            if (g > 0.250) {
                greutateEl.classList.add('alerta');
            } else {
                greutateEl.classList.remove('alerta');
            }

            rosu.classList.remove('activ');
            galben.classList.remove('activ');
            verde.classList.remove('activ');
            status.classList.remove('detectat');

            if (data.culoare === 'rosu') {
                rosu.classList.add('activ');
                status.textContent = 'ROSU - GREUTATE DETECTATA';
                status.classList.add('detectat');
            } else if (data.culoare === 'galben') {
                galben.classList.add('activ');
                status.textContent = 'AVERTIZARE';
            } else if (data.culoare === 'verde') {
                verde.classList.add('activ');
                status.textContent = 'CALE LIBERA';
            } else {
                status.textContent = 'OFFLINE';
            }

            if (data.mesaj && data.mesaj.length > 0) {
                mesajEl.textContent = data.mesaj;
                mesajEl.classList.add('activ');
            } else {
                mesajEl.classList.remove('activ');
                mesajEl.textContent = '';
            }
        })
        .catch(() => {
            document.getElementById('semafor-status').textContent = 'OFFLINE';
        });
}
updateGDS();
setInterval(updateGDS, 1000);
</script>
</body>
</html>
