<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

require 'db.php';
$db = getDB();

$poze_dir = __DIR__ . '/poze_semafor';
$poze = [];
if (is_dir($poze_dir)) {
    $files = glob($poze_dir . '/*.jpg');
    usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
    $poze = $files;
}

$numere = $db->query("SELECT * FROM numere_masini ORDER BY data_detectie DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detectii Semafor — BRIDGEGUARD</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .detectii-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--clr-border);
        }
        .section-title {
            font-family: var(--font-mono);
            font-size: 1rem;
            color: var(--clr-accent);
            letter-spacing: 0.05em;
            margin: 0;
        }
        .section-count {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-muted);
            background: rgba(255,255,255,0.05);
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid var(--clr-border);
        }

        .tab-toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 16px;
        }
        .btn-delete-all {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            padding: 8px 16px;
            background: rgba(255,26,26,0.1);
            border: 1px solid rgba(255,26,26,0.3);
            color: #ff4444;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-delete-all:hover {
            background: rgba(255,26,26,0.2);
            border-color: #ff4444;
        }

        .detectii-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .detectie-card {
            background: var(--clr-bg-alt);
            border: 1px solid var(--clr-border);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .detectie-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        .detectie-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .detectie-info {
            padding: 12px 16px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--clr-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .detectie-data {
            color: var(--clr-accent);
            font-weight: 600;
        }
        .btn-delete {
            background: rgba(255,26,26,0.15);
            border: 1px solid rgba(255,26,26,0.3);
            color: #ff4444;
            font-family: var(--font-mono);
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background: rgba(255,26,26,0.3);
            border-color: #ff4444;
        }

        .numere-table {
            width: 100%;
            border-collapse: collapse;
            font-family: var(--font-mono);
            font-size: 0.85rem;
        }
        .numere-table th {
            text-align: left;
            padding: 14px 16px;
            background: rgba(0,0,0,0.3);
            color: var(--clr-accent);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid var(--clr-border);
        }
        .numere-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--clr-border);
            color: var(--clr-text);
        }
        .numere-table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .numar-plate {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.05em;
            color: #fff;
            background: linear-gradient(135deg, #1a3a5c, #0d253b);
            border: 1px solid var(--clr-accent);
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--clr-muted);
            font-family: var(--font-mono);
        }
        .empty-state .icon { font-size: 3rem; margin-bottom: 16px; }
    </style>
</head>
<body>

<div class="cursor-glow" id="cursorGlow"></div>

<nav class="navbar">
    <div class="nav-brand">
        <span class="nav-brand-title">BRIDGEGUARD</span>
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
            </div>
        </div>
    </div>
    <div class="nav-right">
        <div class="dropdown">
            <button class="dropbtn"><?= htmlspecialchars($_SESSION['user']) ?> ▾</button>
            <div class="dropdown-menu">
                <a href="settings.php">Setari cont</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="detectii-container">

    <div class="section-header">
        <h2 class="section-title">Numere Masini Detectate</h2>
        <span class="section-count"><?= count($numere) ?> inregistrari</span>
    </div>

    <div class="numere-section" id="tab-numere">
        <table class="numere-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Numar masina</th>
                    <th>Poza</th>
                    <th>Data</th>
                    <th>Ora</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="numere-tbody">
                <?php if (empty($numere)): ?>
                    <tr id="empty-row">
                        <td colspan="6" style="text-align:center; padding:40px; color:var(--clr-muted);">
                            Niciun numar detectat inca. Numerele apar automat dupa ce camera fotografiaza.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($numere as $i => $n): ?>
                        <tr id="numar-<?= $n['id'] ?>">
                            <td><?= $i + 1 ?></td>
                            <td><span class="numar-plate"><?= htmlspecialchars($n['numar']) ?></span></td>
                            <td>
                                <?php
                                    $poza_file = basename($n['poza']);
                                    if (file_exists(__DIR__ . '/poze_semafor/' . $poza_file)):
                                ?>
                                    <a href="poze_semafor/<?= $poza_file ?>" target="_blank" style="color:var(--clr-accent);">vezi poza</a>
                                <?php else: ?>
                                    <span style="color:var(--clr-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($n['data_detectie'])) ?></td>
                            <td><?= date('H:i:s', strtotime($n['data_detectie'])) ?></td>
                            <td><button class="btn-delete" onclick="deleteNumar(<?= $n['id'] ?>)">Sterge</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-header" style="margin-top: 40px;">
        <h2 class="section-title">Poze Detectii</h2>
        <span class="section-count"><?= count($poze) ?> poze</span>
    </div>

    <div id="tab-poze">
        <?php if (empty($poze)): ?>
            <div class="empty-state">
                <div class="icon">📷</div>
                <p>Nicio detectie inca.<br>Pozele apar automat cand senzorul 2 detecteaza un obiect.</p>
            </div>
        <?php else: ?>
            <div class="tab-toolbar">
                <button class="btn-delete-all" onclick="deleteAll()">Sterge toate pozele</button>
            </div>
            <div class="detectii-grid">
                <?php foreach ($poze as $poza): ?>
                    <?php
                        $filename = basename($poza);
                        $ts = str_replace(['detectie_', '.jpg'], '', $filename);
                        $parts = explode('_', $ts);
                        if (count($parts) == 2) {
                            $d = $parts[0]; $t = $parts[1];
                            $data_fmt = substr($d,6,2).'/'.substr($d,4,2).'/'.substr($d,0,4);
                            $ora_fmt = substr($t,0,2).':'.substr($t,2,2).':'.substr($t,4,2);
                        } else {
                            $data_fmt = date('d/m/Y', filemtime($poza));
                            $ora_fmt = date('H:i:s', filemtime($poza));
                        }
                    ?>
                    <div class="detectie-card" id="poza-<?= md5($filename) ?>">
                        <a href="poze_semafor/<?= $filename ?>" target="_blank">
                            <img src="poze_semafor/<?= $filename ?>" alt="Detectie">
                        </a>
                        <div class="detectie-info">
                            <span><span class="detectie-data"><?= $data_fmt ?></span> la <?= $ora_fmt ?></span>
                            <button class="btn-delete" onclick="deletePoza('<?= $filename ?>', '<?= md5($filename) ?>')">Sterge</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function deletePoza(filename, hash) {
    if (!confirm('Stergi aceasta poza?')) return;
    fetch('api_detectii.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete_poza', filename: filename})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('poza-' + hash).remove();
        }
    });
}

function deleteNumar(id) {
    if (!confirm('Stergi acest numar si poza asociata?')) return;
    fetch('api_detectii.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete_numar', id: id})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('numar-' + id).remove();
        }
    });
}

function deleteAll() {
    if (!confirm('Stergi TOATE pozele si numerele? Aceasta actiune nu poate fi anulata.')) return;
    fetch('api_detectii.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete_all_poze'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

</body>
</html>
