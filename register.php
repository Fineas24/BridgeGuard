<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}



require_once 'db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nume      = trim($_POST['nume']      ?? '');
    $parola    = trim($_POST['parola']    ?? '');
    $cod_admin = trim($_POST['cod_admin'] ?? '');

    if ($nume === '' || $parola === '' || $cod_admin === '') {
        $error = 'Toate câmpurile sunt obligatorii.';
    } elseif (strlen($nume) < 3) {
        $error = 'Numele trebuie să aibă cel puțin 3 caractere.';
    } elseif (strlen($parola) < 6) {
        $error = 'Parola trebuie să aibă cel puțin 6 caractere.';
    } else {
        $db = getDB();
        
        $stmt_cod = $db->prepare("SELECT id FROM coduri_acces WHERE `cod administrator` = :cod LIMIT 1");
        $stmt_cod->execute([':cod' => $cod_admin]);
        
        if (!$stmt_cod->fetch()) {
            $error = 'Codul de administrator este incorect.';
        } else {
            $stmt = $db->prepare('SELECT id FROM conect WHERE nume = :nume LIMIT 1');
            $stmt->execute([':nume' => $nume]);

            if ($stmt->fetch()) {
                $error = 'Acest nume este deja folosit.';
            } else {
                $hash = password_hash($parola, PASSWORD_BCRYPT);
                $ins  = $db->prepare('INSERT INTO conect (nume, parola, cod_admin) VALUES (:nume, :parola, :cod)');
                $ins->execute([':nume' => $nume, ':parola' => $hash, ':cod' => $cod_admin]);

                $success = 'Cont creat cu succes! Te poți <a href="index.php" style="color:#00d97e">autentifica acum</a>.';
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
    <title>Înregistrare — BRIDGEWATCH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #0d1117;
            color: #e6edf3;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: #161b22;
            padding: 40px;
            border-radius: 8px;
            border: 1px solid #30363d;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            text-align: center;
            color: #00d97e;
            letter-spacing: 2px;
        }
        p.subtitle {
            text-align: center;
            font-size: 0.8rem;
            color: #8b949e;
            margin-bottom: 24px;
        }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.8rem;
            color: #8b949e;
        }
        input {
            width: 100%;
            background: #0d1117;
            border: 1px solid #30363d;
            padding: 12px;
            border-radius: 4px;
            color: #e6edf3;
            font-family: inherit;
        }
        input:focus {
            outline: none;
            border-color: #00d97e;
        }
        .btn {
            width: 100%;
            background: #00d97e;
            color: #0d1117;
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .error {
            color: #ef4444;
            font-size: 0.8rem;
            margin-bottom: 16px;
            text-align: center;
        }
        .success {
            color: #00d97e;
            font-size: 0.8rem;
            margin-bottom: 16px;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.8rem;
            color: #8b949e;
        }
        .footer a { color: #00d97e; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>BRIDGEWATCH</h1>
        <p class="subtitle">CREARE CONT NOU</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>UTILIZATOR</label>
                    <input type="text" name="nume" required autofocus>
                </div>
                <div class="form-group">
                    <label>PAROLĂ</label>
                    <input type="password" name="parola" required>
                </div>
                <div class="form-group">
                    <label>COD ADMINISTRATOR</label>
                    <input type="text" name="cod_admin" required placeholder="Introduceți codul de acces">
                </div>
                <button type="submit" class="btn">CREEAZĂ CONT</button>
            </form>
        <?php endif; ?>

        <div class="footer">
            Ai deja cont? <a href="index.php">Autentifică-te</a>
        </div>
    </div>
</body>
</html>
