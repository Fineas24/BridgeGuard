<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

require 'db.php';
$db = getDB();

$sensor_id = intval($_GET['sensor_id'] ?? 1);
$is_gyro   = ($sensor_id === 6);

$stmt = $db->prepare("SELECT * FROM sensors WHERE id = ?");
$stmt->execute([$sensor_id]);
$sensor = $stmt->fetch();
if (!$sensor) die('Senzor invalid.');

$stmt_stop = $db->prepare("SELECT id FROM alerts WHERE sensor_id=? AND status='activ' LIMIT 1");
$stmt_stop->execute([$sensor_id]);
$has_stop = (bool) $stmt_stop->fetch();

$stmt_urg = $db->prepare("SELECT id FROM urgente_mesaje WHERE sensor_id=? AND status='activ' LIMIT 1");
$stmt_urg->execute([$sensor_id]);
$has_urg = (bool) $stmt_urg->fetch();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sensor['name']) ?> — BRIDGEGUARD</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .detail-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .detail-header h1 {
            font-size: 1.3rem;
            margin: 0;
        }
        .detail-header .sensor-unit {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-subtle);
            background: var(--clr-accent-dim);
            padding: 4px 12px;
            border-radius: 999px;
            border: 1px solid rgba(0, 217, 126, 0.1);
        }

        .chart-large {
            height: 380px;
            transition: box-shadow 0.3s;
        }
        .chart-large:hover {
            box-shadow: 0 0 30px rgba(0, 217, 126, 0.05);
        }

        .action-bar {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        .action-btn {
            flex: 1;
            padding: 16px 20px;
            border: none;
            border-radius: 14px;
            font-family: var(--font-body);
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s var(--ease-spring);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        .action-btn:hover {
            transform: translateY(-2px);
        }
        .action-btn:active {
            transform: translateY(0) scale(0.98);
        }
        .action-btn.btn-urg {
            background: linear-gradient(135deg, #ffaa00, #ff8800);
            color: #1a1a1a;
        }
        .action-btn.btn-urg:hover {
            box-shadow: 0 8px 30px rgba(255, 170, 0, 0.3);
        }
        .action-btn.btn-stp {
            background: linear-gradient(135deg, #ff3b5c, #cc2244);
            color: #fff;
        }
        .action-btn.btn-stp:hover {
            box-shadow: 0 8px 30px rgba(255, 59, 92, 0.3);
        }
        .action-btn.btn-rst {
            background: linear-gradient(135deg, #00d97e, #00a67a);
            color: #fff;
            flex: 0 0 120px;
        }
        .action-btn.btn-rst:hover {
            box-shadow: 0 8px 30px rgba(0, 200, 150, 0.3);
        }
    </style>
</head>
<body class="<?= $has_stop ? 'page-alert-stop' : '' ?>">

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
    <a href="dashboard.php" class="back-link">← Dashboard</a>

    <div class="detail-header">
        <h1><?= htmlspecialchars($sensor['name']) ?></h1>
        <span class="sensor-unit"><?= htmlspecialchars($sensor['unit']) ?> · #<?= $sensor_id ?></span>
    </div>

    <div class="chart-large">
        <canvas id="mainChart"></canvas>
    </div>

    <div class="kpi-grid">
<?php if ($is_gyro): ?>
        <div class="kpi-card">
            <div class="kpi-label" style="color:#00d97e;">FAȚĂ-SPATE</div>
            <div class="kpi-value" id="kpi-fs" style="color:#00d97e;">—°</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label" style="color:#34d399;">STÂNGA-DREAPTA</div>
            <div class="kpi-value" id="kpi-sd" style="color:#34d399;">—°</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">MIN</div>
            <div class="kpi-value" id="kpi-min">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">MAX</div>
            <div class="kpi-value" id="kpi-max">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">PICO</div>
            <div id="kpi-status"><span class="badge badge-normal" id="pico-badge">AȘTEPTARE</span></div>
        </div>
<?php else: ?>
        <div class="kpi-card">
            <div class="kpi-label">CURENT</div>
            <div class="kpi-value" id="kpi-cur">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">MIN</div>
            <div class="kpi-value" id="kpi-min">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">MAX</div>
            <div class="kpi-value" id="kpi-max">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">MEDIE</div>
            <div class="kpi-value" id="kpi-avg">—</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">STATUS</div>
            <div id="kpi-status"><span class="badge badge-normal">NORMAL</span></div>
        </div>
<?php endif; ?>
    </div>

    <div class="action-bar">
        <button class="action-btn btn-urg" onclick="openUrgModal()">URGENȚĂ</button>
        <button class="action-btn btn-stp" onclick="triggerStop()">STOP TRAFIC</button>
        <button class="action-btn btn-rst" onclick="resetStop()">REVENIRE</button>
    </div>

</main>

<div class="modal-overlay" id="urg-modal">
    <div class="modal-box">
        <div class="modal-title">Trimite mesaj de urgență</div>
        <textarea id="urg-msg" rows="4" placeholder="Descrieți problema sesizată..."></textarea>
        <div style="display:flex;gap:12px;">
            <button class="action-btn" style="background:var(--clr-surface);color:var(--clr-text-body);flex:1;border:1px solid var(--clr-border);" onclick="closeUrgModal()">Anulează</button>
            <button class="action-btn btn-urg" style="flex:2;" onclick="submitUrgency()">Trimite</button>
        </div>
    </div>
</div>

<script>
const SENSOR_ID = <?= $sensor_id ?>;
const UNIT      = <?= json_encode($sensor['unit']) ?>;
const IS_GYRO   = <?= $is_gyro ? 'true' : 'false' ?>;
let   history_vals = [];
let   history_sd   = [];

const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top = e.clientY + 'px';
});

Chart.defaults.color = '#5a6a80';
Chart.defaults.borderColor = 'rgba(60, 180, 100, 0.04)';
Chart.defaults.font.family = "'JetBrains Mono', monospace";

const ctx = document.getElementById('mainChart').getContext('2d');
const mainChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: Array(80).fill(''),
        datasets: IS_GYRO ? [
            {
                label: 'Față-Spate',
                data: Array(80).fill(null),
                borderColor: '#00d97e',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.4,
                fill: false,
                cubicInterpolationMode: 'monotone'
            },
            {
                label: 'Stânga-Dreapta',
                data: Array(80).fill(null),
                borderColor: '#34d399',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.4,
                fill: false,
                cubicInterpolationMode: 'monotone'
            }
        ] : [{
            data: Array(80).fill(null),
            borderColor: '#00d97e',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.4,
            fill: true,
            backgroundColor: 'rgba(0, 217, 126, 0.04)',
            cubicInterpolationMode: 'monotone'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: IS_GYRO, labels: { color: '#5a6a80', font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } }
        },
        scales: {
            x: {
                display: true,
                grid: { color: 'rgba(60, 180, 100, 0.03)' },
                ticks: { display: false }
            },
            y: {
                display: true,
                grid: { color: 'rgba(60, 180, 100, 0.04)' },
                title: IS_GYRO ? { display: true, text: 'Grade (°)', color: '#5a6a80' } : { display: false },
                ticks: { font: { size: 10 } }
            }
        },
        animation: { duration: 200 },
        elements: {
            line: { capBezierPoints: true }
        }
    }
});

setInterval(() => {
    fetch(`api_sensor.php?action=value&id=${SENSOR_ID}`)
        .then(r => r.json())
        .then(d => {
            if (IS_GYRO) {
                const dsFS = mainChart.data.datasets[0];
                const dsSD = mainChart.data.datasets[1];

                if (d.online) {
                    const fs = d.fata_spate;
                    const sd = d.stanga_dreapta;

                    dsFS.data.push(fs); if (dsFS.data.length > 80) dsFS.data.shift();
                    dsSD.data.push(sd); if (dsSD.data.length > 80) dsSD.data.shift();
                    mainChart.update('none');

                    history_vals.push(fs);
                    history_sd.push(sd);
                    if (history_vals.length > 120) history_vals.shift();
                    if (history_sd.length > 120) history_sd.shift();

                    const colorFS = Math.abs(fs) > 15 ? '#ff3b5c' : Math.abs(fs) > 10 ? '#ffaa00' : '#00d97e';
                    const colorSD = Math.abs(sd) > 15 ? '#ff3b5c' : Math.abs(sd) > 10 ? '#ffaa00' : '#34d399';

                    const elFS = document.getElementById('kpi-fs');
                    const elSD = document.getElementById('kpi-sd');
                    if (elFS) { elFS.textContent = (fs>=0?'+':'')+fs+'°'; elFS.style.color = colorFS; }
                    if (elSD) { elSD.textContent = (sd>=0?'+':'')+sd+'°'; elSD.style.color = colorSD; }

                    const mn = Math.min(...history_vals).toFixed(1);
                    const mx = Math.max(...history_vals).toFixed(1);
                    const elMin = document.getElementById('kpi-min');
                    const elMax = document.getElementById('kpi-max');
                    if (elMin) elMin.textContent = mn + '°';
                    if (elMax) elMax.textContent = mx + '°';

                    const badge = document.getElementById('pico-badge');
                    if (badge) {
                        badge.textContent = 'ONLINE';
                        badge.className = 'badge badge-normal';
                    }
                } else {
                    const badge = document.getElementById('pico-badge');
                    if (badge) {
                        badge.textContent = 'OFFLINE';
                        badge.className = 'badge badge-critic';
                    }
                }
            } else {
                history_vals.push(d.value);
                if (history_vals.length > 120) history_vals.shift();

                const ds = mainChart.data.datasets[0];
                ds.data.push(d.value); if (ds.data.length > 80) ds.data.shift();
                mainChart.update('none');

                const cur = d.value;
                const mn  = Math.min(...history_vals).toFixed(2);
                const mx  = Math.max(...history_vals).toFixed(2);
                const avg = (history_vals.reduce((a,b)=>a+b,0)/history_vals.length).toFixed(2);

                document.getElementById('kpi-cur').textContent = cur + ' ' + UNIT;
                document.getElementById('kpi-min').textContent = mn + ' ' + UNIT;
                document.getElementById('kpi-max').textContent = mx + ' ' + UNIT;
                document.getElementById('kpi-avg').textContent = avg + ' ' + UNIT;

                const pct = (cur - mn) / (mx - mn + 0.001);
                const sc  = document.getElementById('kpi-status');
                if (pct > 0.85) sc.innerHTML = '<span class="badge badge-critic">CRITIC</span>';
                else if (pct > 0.65) sc.innerHTML = '<span class="badge badge-atentie">ATENȚIE</span>';
                else sc.innerHTML = '<span class="badge badge-normal">NORMAL</span>';
            }
        });
}, 400);

function openUrgModal()  { document.getElementById('urg-modal').classList.add('active'); }
function closeUrgModal() { document.getElementById('urg-modal').classList.remove('active'); }

function submitUrgency() {
    const msg = document.getElementById('urg-msg').value.trim();
    if (!msg) { alert('Scrie un mesaj înainte de a trimite.'); return; }

    fetch('api_sensor.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'urgenta', sensor_id: SENSOR_ID, mesaj: msg })
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            closeUrgModal();
            document.getElementById('urg-msg').value = '';
            showNotif('Mesaj de urgență trimis!', 'yellow');
        }
    });
}

function triggerStop() {
    if (!confirm('Activezi STOP TRAFIC pentru acest senzor?')) return;
    fetch('api_sensor.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'stop_trafic', sensor_id: SENSOR_ID })
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            document.body.classList.add('page-alert-stop');
            showNotif('Stop trafic activat!', 'red');
        }
    });
}

function resetStop() {
    fetch('api_sensor.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'stop_trafic_off', sensor_id: SENSOR_ID })
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            document.body.classList.remove('page-alert-stop');
            showNotif('Alertă resetată.', 'green');
        }
    });
}

function showNotif(msg, color) {
    const el = document.createElement('div');
    el.textContent = msg;
    const borderC = color === 'red' ? 'var(--danger)' : color === 'yellow' ? 'var(--warning)' : 'var(--accent)';
    const textC   = borderC;
    el.style.cssText = `
        position:fixed; bottom:28px; right:28px; z-index:9999;
        background: var(--clr-surface); border: 1px solid ${borderC};
        color: ${textC};
        padding: 14px 24px; border-radius: 10px;
        font-size: 0.85rem; font-family: var(--font-mono);
        box-shadow: 0 8px 30px rgba(0,0,0,0.4);
        animation: dd-in 0.25s var(--ease-out);
    `;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        ripple.classList.add('btn-ripple');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
    });
});
</script>
</body>
</html>
