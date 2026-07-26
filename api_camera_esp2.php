<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautorizat']);
    exit;
}

header('Content-Type: application/json');

require_once __DIR__ . '/config.local.php';

$BASE_DIR     = '/var/www/html/proiect';
$SAVE_DIR     = "$BASE_DIR/uploads/poze_camera2";
$VENV_PYTHON  = "$BASE_DIR/Python/detect_poze/bin/python3";
$ESP_CAPTURE  = 'http://192.168.100.218/capture';

if (!is_dir($SAVE_DIR)) {
    mkdir($SAVE_DIR, 0777, true);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'detect':
        $timestamp    = date('d-m-Y___H-i-s');
        $cap_filename = "cam2_{$timestamp}.jpg";
        $det_filename = "cam2_{$timestamp}_fisuri.jpg";
        $cap_path     = "$SAVE_DIR/$cap_filename";
        $det_path     = "$SAVE_DIR/$det_filename";

        $ch = curl_init($ESP_CAPTURE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $imageData = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr   = curl_error($ch);
        curl_close($ch);

        if (!$imageData || $httpCode !== 200) {
            echo json_encode([
                'success' => false,
                'error'   => "Nu pot captura de pe ESP32-CAM: " . ($curlErr ?: "HTTP $httpCode")
            ]);
            exit;
        }

        file_put_contents($cap_path, $imageData);

        $detect_script = <<<PYEOF
import json, sys, os
os.environ['HOME'] = '/tmp'
os.environ['ROBOFLOW_CONFIG_DIR'] = '/tmp/.roboflow'

from roboflow import Roboflow

rf = Roboflow(api_key=os.environ['ROBOFLOW_API_KEY'])
project = rf.workspace().project("crack-btlnb")
model = project.version(1).model

prediction = model.predict("$cap_path", confidence=40)
prediction.save("$det_path")

results = prediction.json()
predictions = results.get('predictions', []) if isinstance(results, dict) else results
output = {
    'has_cracks': len(predictions) > 0,
    'count': len(predictions),
    'predictions': predictions
}
print(json.dumps(output))
PYEOF;

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $env = [
            'HOME' => '/tmp',
            'PATH' => '/usr/local/bin:/usr/bin:/bin',
            'TMPDIR' => '/tmp',
            'ROBOFLOW_API_KEY' => ROBOFLOW_API_KEY,
        ];

        $process = proc_open(
            [$VENV_PYTHON, '-c', $detect_script],
            $descriptorspec,
            $pipes,
            "$BASE_DIR/Python",
            $env
        );

        $detection_data = ['has_cracks' => false, 'count' => 0];

        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $retcode = proc_close($process);

            if ($retcode === 0 && $stdout) {
                $lines = explode("\n", trim($stdout));
                $lastLine = end($lines);
                $parsed = json_decode($lastLine, true);
                if ($parsed) {
                    $detection_data = $parsed;
                }
            } else {
                $detection_data['error'] = substr($stderr, -500);
            }
        }

        if (!file_exists($det_path)) {
            copy($cap_path, $det_path);
            $detection_data['detection_failed'] = true;
            if (!empty($stderr)) {
                $detection_data['debug_error'] = substr($stderr, -500);
            }
        }

        echo json_encode(array_merge([
            'success'       => true,
            'capture'       => "uploads/poze_camera2/$cap_filename",
            'detection'     => "uploads/poze_camera2/$det_filename",
            'cap_filename'  => $cap_filename,
            'det_filename'  => $det_filename,
            'timestamp'     => $timestamp,
        ], $detection_data));
        break;

    default:
        echo json_encode(['error' => 'Acțiune necunoscută']);
}
