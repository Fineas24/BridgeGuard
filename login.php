<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'db.php';
$db = getDB();

$error_login = '';
$error_reg   = '';
$active_tab  = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error_login = 'Completează ambele câmpuri.';
    } else {
        $stmt = $db->prepare("SELECT id, username AS user, password AS pass, nume_pod FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $u = $stmt->fetch();

        if (!$u) {
            $stmt2 = $db->prepare("SELECT id, nume AS user, parola AS pass, '' AS nome_pod FROM conect WHERE nume = ? LIMIT 1");
            $stmt2->execute([$username]);
            $u = $stmt2->fetch();
            if ($u) {
                $stmtCod = $db->prepare("SELECT `tip pod` FROM coduri_acces WHERE `cod administrator` = (SELECT cod_admin FROM conect WHERE id = ?) LIMIT 1");
                $stmtCod->execute([$u['id']]);
                $u['nome_pod'] = $stmtCod->fetchColumn() ?: '';
            }
        }

        if ($u && password_verify($password, $u['pass'])) {
            session_regenerate_id(true);
            $_SESSION['user']     = $u['user'];
            $_SESSION['user_id']  = $u['id'];
            $_SESSION['user_name']= $u['user'];
            $_SESSION['nume_pod'] = $u['nome_pod'] ?? '';
            header('Location: dashboard.php');
            exit;
        } else {
            $error_login = 'Utilizator sau parolă incorectă.';
            $active_tab  = 'login';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'register') {
    $active_tab  = 'register';
    $username    = trim($_POST['username']  ?? '');
    $password    = trim($_POST['password']  ?? '');
    $password2   = trim($_POST['password2'] ?? '');
    $cod_acces   = trim($_POST['cod_acces'] ?? '');

    if ($username === '' || $password === '' || $cod_acces === '') {
        $error_reg = 'Toate câmpurile sunt obligatorii.';
    } elseif (strlen($username) < 3) {
        $error_reg = 'Utilizatorul trebuie să aibă minim 3 caractere.';
    } elseif (strlen($password) < 6) {
        $error_reg = 'Parola trebuie să aibă minim 6 caractere.';
    } elseif ($password !== $password2) {
        $error_reg = 'Parolele nu coincid.';
    } else {
        $stmt = $db->prepare("SELECT `tip pod` FROM coduri_acces WHERE `cod administrator` = ? LIMIT 1");
        $stmt->execute([$cod_acces]);
        $pod = $stmt->fetchColumn();

        if ($pod === false) {
            $error_reg = 'Cod administrator incorect sau invalid.';
        } else {
            $stmt2 = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt2->execute([$username]);
            if ($stmt2->fetch()) {
                $error_reg = 'Utilizatorul există deja.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins  = $db->prepare("INSERT INTO users (username, password, cod_acces, nume_pod) VALUES (?, ?, ?, ?)");
                $ins->execute([$username, $hash, $cod_acces, $pod]);

                session_regenerate_id(true);
                $_SESSION['user']      = $username;
                $_SESSION['user_id']   = $db->lastInsertId();
                $_SESSION['user_name'] = $username;
                $_SESSION['nume_pod']  = $pod;
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BRIDGEGUARD — Autentificare</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .auth-wrap::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 217, 126, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 217, 126, 0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at center, black 20%, transparent 60%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 60%);
            pointer-events: none;
        }
        .auth-card {
            animation: card-in 0.5s var(--ease-smooth);
        }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(16px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .btn-primary {
            width: 100%;
            text-align: center;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="cursor-glow" id="cursorGlow"></div>

<nav class="navbar" style="position: absolute; width: 100%; border-bottom: none; background: transparent;">
    <div class="nav-brand">
        <a href="index.php" style="text-decoration:none;">
            <span class="nav-brand-title">BRIDGEGUARD</span>
        </a>
    </div>
</nav>

<div class="auth-wrap">
    <div class="auth-card">

        <div class="auth-logo">
            <div class="logo-title">BRIDGEGUARD</div>
            <div class="logo-sub">Sistem de monitorizare structuri</div>
        </div>

        <div class="tab-row">
            <button class="tab-btn <?= $active_tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">CONECTARE</button>
            <button class="tab-btn <?= $active_tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">CONT NOU</button>
        </div>

        <div id="tab-login" class="tab-panel <?= $active_tab === 'login' ? 'active' : '' ?>">
            <?php if ($error_login): ?>
                <div class="alert-msg error"><?= htmlspecialchars($error_login) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off" novalidate>
                <input type="hidden" name="form" value="login">
                <div class="form-group">
                    <label>Utilizator</label>
                    <input type="text" name="username" required autofocus
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Parolă</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Conectare</button>
            </form>
        </div>

        <div id="tab-register" class="tab-panel <?= $active_tab === 'register' ? 'active' : '' ?>">
            <?php if ($error_reg): ?>
                <div class="alert-msg error"><?= htmlspecialchars($error_reg) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off" novalidate>
                <input type="hidden" name="form" value="register">
                <div class="form-group">
                    <label>Utilizator nou</label>
                    <input type="text" name="username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Parolă</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirmă parola</label>
                    <input type="password" name="password2" required>
                </div>
                <div class="form-group">
                    <label style="color: var(--warning);">Cod Administrator</label>
                    <input type="password" name="cod_acces" required placeholder="Cod primit de la administrator">
                    <div class="form-hint">Necesar pentru crearea contului.</div>
                </div>
                <button type="submit" class="btn-primary">Creează cont</button>
            </form>
        </div>

    </div>
</div>

<script>
    const glow = document.getElementById('cursorGlow');
    document.addEventListener('mousemove', e => {
        glow.style.left = e.clientX + 'px';
        glow.style.top = e.clientY + 'px';
    });

    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`#tab-${tab}`).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    document.querySelectorAll('.btn-primary').forEach(btn => {
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

    document.querySelectorAll('.form-group input').forEach(inp => {
        inp.addEventListener('focus', () => inp.parentElement.style.filter = 'drop-shadow(0 0 8px rgba(0, 217, 126, 0.1))');
        inp.addEventListener('blur', () => inp.parentElement.style.filter = 'none');
    });
</script>
</body>
</html>
