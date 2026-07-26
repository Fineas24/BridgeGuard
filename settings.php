<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }

require 'db.php';
$db = getDB();

$msg_name = '';
$msg_pass = '';
$err_name = '';
$err_pass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'change_name') {
    $new_name   = trim($_POST['new_name']        ?? '');
    $cod        = trim($_POST['cod_admin_name']   ?? '');
    $cur_pass   = trim($_POST['cur_pass_name']    ?? '');

    if ($new_name === '' || $cod === '' || $cur_pass === '') {
        $err_name = 'Toate câmpurile sunt obligatorii.';
    } elseif (strlen($new_name) < 3) {
        $err_name = 'Noul nume trebuie să aibă minim 3 caractere.';
    } else {
        $s = $db->prepare("SELECT id FROM coduri_acces WHERE `cod administrator` = ? LIMIT 1");
        $s->execute([$cod]);
        if (!$s->fetch()) {
            $err_name = 'Cod administrator incorect.';
        } else {
            $user_row = null;
            $tbl = 'users';
            $s2 = $db->prepare("SELECT id, password AS pass FROM users WHERE username = ? LIMIT 1");
            $s2->execute([$_SESSION['user']]);
            $user_row = $s2->fetch();

            if (!$user_row) {
                $tbl = 'conect';
                $s2b = $db->prepare("SELECT id, parola AS pass FROM conect WHERE nume = ? LIMIT 1");
                $s2b->execute([$_SESSION['user']]);
                $user_row = $s2b->fetch();
            }

            if (!$user_row || !password_verify($cur_pass, $user_row['pass'])) {
                $err_name = 'Parola curentă este incorectă.';
            } else {
                $s3 = $db->prepare("SELECT id FROM $tbl WHERE " . ($tbl==='users' ? 'username' : 'nume') . " = ? LIMIT 1");
                $s3->execute([$new_name]);
                if ($s3->fetch()) {
                    $err_name = 'Utilizatorul există deja.';
                } else {
                    $col = $tbl === 'users' ? 'username' : 'nume';
                    $upd = $db->prepare("UPDATE $tbl SET $col = ? WHERE id = ?");
                    $upd->execute([$new_name, $user_row['id']]);
                    $_SESSION['user'] = $_SESSION['user_name'] = $new_name;
                    $msg_name = 'Numele a fost actualizat cu succes!';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'change_pass') {
    $cur_pass  = trim($_POST['cur_pass']    ?? '');
    $new_pass  = trim($_POST['new_pass']    ?? '');
    $new_pass2 = trim($_POST['new_pass2']   ?? '');
    $cod       = trim($_POST['cod_admin_pass'] ?? '');

    if ($cur_pass === '' || $new_pass === '' || $new_pass2 === '' || $cod === '') {
        $err_pass = 'Toate câmpurile sunt obligatorii.';
    } elseif ($new_pass !== $new_pass2) {
        $err_pass = 'Parolele noi nu coincid.';
    } elseif (strlen($new_pass) < 6) {
        $err_pass = 'Parola nouă trebuie să aibă minim 6 caractere.';
    } else {
        $s = $db->prepare("SELECT id FROM coduri_acces WHERE `cod administrator` = ? LIMIT 1");
        $s->execute([$cod]);
        if (!$s->fetch()) {
            $err_pass = 'Cod administrator incorect.';
        } else {
            $tbl = 'users';
            $s2 = $db->prepare("SELECT id, password AS pass FROM users WHERE username = ? LIMIT 1");
            $s2->execute([$_SESSION['user']]);
            $user_row = $s2->fetch();

            if (!$user_row) {
                $tbl = 'conect';
                $s2b = $db->prepare("SELECT id, parola AS pass FROM conect WHERE nume = ? LIMIT 1");
                $s2b->execute([$_SESSION['user']]);
                $user_row = $s2b->fetch();
            }

            if (!$user_row || !password_verify($cur_pass, $user_row['pass'])) {
                $err_pass = 'Parola curentă este incorectă.';
            } else {
                $hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $col  = $tbl === 'users' ? 'password' : 'parola';
                $upd  = $db->prepare("UPDATE $tbl SET $col = ? WHERE id = ?");
                $upd->execute([$hash, $user_row['id']]);
                $msg_pass = 'Parola a fost schimbată cu succes!';
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
    <title>Setări Cont — BRIDGEGUARD</title>
    <link rel="stylesheet" href="assets/style.css">
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
    <div class="nav-right">
        <a href="dashboard.php" class="back-link" style="margin:0;">← Dashboard</a>
    </div>
</nav>

<main class="container">

    <div style="max-width:540px;margin:0 auto;">
        <h1 style="font-size:1rem;letter-spacing:2px;color:var(--clr-accent);margin-bottom:28px;font-family:var(--font-display);">SETĂRI CONT</h1>

        <div class="settings-card">
            <h2>SCHIMBARE UTILIZATOR</h2>

            <?php if ($msg_name): ?><div class="alert-msg success"><?= htmlspecialchars($msg_name) ?></div><?php endif; ?>
            <?php if ($err_name): ?><div class="alert-msg error"><?= htmlspecialchars($err_name) ?></div><?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="form" value="change_name">
                <div class="form-group">
                    <label>UTILIZATOR NOU</label>
                    <input type="text" name="new_name" value="<?= htmlspecialchars($_SESSION['user']) ?>">
                </div>
                <div class="form-group">
                    <label>PAROLĂ CURENTĂ (confirmare)</label>
                    <input type="password" name="cur_pass_name" required>
                </div>
                <div class="form-group">
                    <label style="color:var(--warning);">🔑 COD ADMINISTRATOR</label>
                    <input type="password" name="cod_admin_name" required placeholder="Codul de acces">
                </div>
                <button type="submit" class="btn-primary">SALVEAZĂ UTILIZATOR</button>
            </form>
        </div>

        <div class="settings-card">
            <h2>SCHIMBARE PAROLĂ</h2>

            <?php if ($msg_pass): ?><div class="alert-msg success"><?= htmlspecialchars($msg_pass) ?></div><?php endif; ?>
            <?php if ($err_pass): ?><div class="alert-msg error"><?= htmlspecialchars($err_pass) ?></div><?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="form" value="change_pass">
                <div class="form-group">
                    <label>PAROLĂ CURENTĂ</label>
                    <input type="password" name="cur_pass" required>
                </div>
                <div class="form-group">
                    <label>PAROLĂ NOUĂ</label>
                    <input type="password" name="new_pass" required>
                </div>
                <div class="form-group">
                    <label>CONFIRMĂ PAROLA NOUĂ</label>
                    <input type="password" name="new_pass2" required>
                </div>
                <div class="form-group">
                    <label style="color:var(--warning);">🔑 COD ADMINISTRATOR</label>
                    <input type="password" name="cod_admin_pass" required placeholder="Codul de acces">
                </div>
                <button type="submit" class="btn-primary">SCHIMBĂ PAROLA</button>
            </form>
        </div>

    </div>
</main>
<script>
const glow = document.getElementById('cursorGlow');
document.addEventListener('mousemove', e => {
    glow.style.left = e.clientX + 'px';
    glow.style.top = e.clientY + 'px';
});
</script>
</body>
</html>
