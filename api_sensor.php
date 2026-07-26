<?php
require 'db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'value';

    if ($action === 'value') {
        $id = intval($_GET['id'] ?? 1);

        if (in_array($id, [1, 4, 6, 7])) {
            $SENSORS_FILE  = '/var/www/html/proiect/sensors_latest.json';
            $STALE_SECONDS = 15;

            $data   = null;
            $online = false;
            if (file_exists($SENSORS_FILE)) {
                $raw  = file_get_contents($SENSORS_FILE);
                $data = json_decode($raw, true);
                if ($data) {
                    $age    = time() - (int)($data['timestamp'] ?? 0);
                    $online = ($age < $STALE_SECONDS);
                }
            }

            if ($id === 6) {
                echo json_encode([
                    'ok'             => true,
                    'sensor_id'      => 6,
                    'value'          => $online ? $data['gyro']['fata_spate']     : null,
                    'fata_spate'     => $online ? $data['gyro']['fata_spate']     : null,
                    'stanga_dreapta' => $online ? $data['gyro']['stanga_dreapta'] : null,
                    'online'         => $online
                ]);
                exit;
            }

            if ($id === 1) {
                echo json_encode([
                    'ok'        => true,
                    'sensor_id' => 1,
                    'value'     => $online ? $data['vibratie'] : null,
                    'online'    => $online
                ]);
                exit;
            }

            if ($id === 4) {
                echo json_encode([
                    'ok'        => true,
                    'sensor_id' => 4,
                    'value'     => $online ? $data['ultrasonic'] : null,
                    'online'    => $online
                ]);
                exit;
            }

            if ($id === 7) {
                echo json_encode([
                    'ok'        => true,
                    'sensor_id' => 7,
                    'value'     => $online ? $data['bobinaj'] : null,
                    'online'    => $online
                ]);
                exit;
            }
        }

        if ($id === 2) {
            $TEMP_FILE     = '/var/www/html/proiect/temp_latest.json';
            $STALE_SECONDS = 15;
            $online = false;
            $temp   = null;

            if (file_exists($TEMP_FILE)) {
                $raw  = file_get_contents($TEMP_FILE);
                $data = json_decode($raw, true);
                if ($data) {
                    $age    = time() - (int)($data['timestamp'] ?? 0);
                    $online = ($age < $STALE_SECONDS);
                    $temp   = $online ? round((float)$data['temperatura'], 1) : null;
                }
            }

            echo json_encode([
                'ok'        => true,
                'sensor_id' => 2,
                'value'     => $temp,
                'online'    => $online
            ]);
            exit;
        }

        switch ($id) {
            case 3: $v = rand(150, 1400);                  break;
            case 5: $v = round(40 + rand(-60,60)/10, 1);  break;
            default: $v = round(rand(10,100), 2);
        }
        echo json_encode(['ok' => true, 'sensor_id' => $id, 'value' => $v]);
    }

    if ($action === 'status') {
        $id = intval($_GET['id'] ?? 1);

        $stmt = $db->prepare("SELECT id FROM alerts WHERE sensor_id = ? AND status = 'activ' LIMIT 1");
        $stmt->execute([$id]);
        $has_stop = (bool) $stmt->fetch();

        $stmt2 = $db->prepare("SELECT id, mesaj FROM urgente_mesaje WHERE sensor_id = ? AND status = 'activ' ORDER BY id DESC LIMIT 1");
        $stmt2->execute([$id]);
        $urg = $stmt2->fetch();

        echo json_encode([
            'ok'        => true,
            'sensor_id' => $id,
            'stop'      => $has_stop,
            'urgenta'   => $urg ? true : false,
            'mesaj'     => $urg ? $urg['mesaj'] : null,
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';
    $sid    = intval($body['sensor_id'] ?? 0);

    if ($action === 'urgenta') {
        $mesaj = trim($body['mesaj'] ?? '');
        if ($sid <= 0 || $mesaj === '') {
            echo json_encode(['ok' => false, 'error' => 'Date invalide.']);
            exit;
        }
        $stmt = $db->prepare("INSERT INTO urgente_mesaje (sensor_id, mesaj, status) VALUES (?, ?, 'activ')");
        $stmt->execute([$sid, $mesaj]);
        echo json_encode(['ok' => true]);
    }

    if ($action === 'stop_trafic') {
        if ($sid <= 0) { echo json_encode(['ok' => false]); exit; }
        $stmt = $db->prepare("INSERT INTO alerts (sensor_id, alert_type, status) VALUES (?, 'stop_trafic', 'activ')");
        $stmt->execute([$sid]);
        echo json_encode(['ok' => true]);
    }

    if ($action === 'stop_trafic_off') {
        if ($sid <= 0) { echo json_encode(['ok' => false]); exit; }
        $stmt1 = $db->prepare("UPDATE alerts SET status = 'rezolvat' WHERE sensor_id = ? AND status = 'activ'");
        $stmt1->execute([$sid]);
        $stmt2 = $db->prepare("UPDATE urgente_mesaje SET status = 'rezolvat' WHERE sensor_id = ? AND status = 'activ'");
        $stmt2->execute([$sid]);
        
        echo json_encode(['ok' => true]);
    }
}
?>
