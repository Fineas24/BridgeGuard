<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautorizat']);
    exit;
}

header('Content-Type: application/json');

$CAMERA_SERVER = 'http://127.0.0.1:5001';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    case 'capture':
        $ch = curl_init("$CAMERA_SERVER/capture");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        echo $resp ?: json_encode(['success' => false, 'error' => $err ?: 'Camera server offline']);
        break;

    case 'detect':
        $ch = curl_init("$CAMERA_SERVER/detect");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        echo $resp ?: json_encode(['success' => false, 'error' => $err ?: 'Camera server offline']);
        break;

    case 'list_captures':
        $ch = curl_init("$CAMERA_SERVER/photos/captures");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $resp = curl_exec($ch);
        curl_close($ch);
        echo $resp ?: '[]';
        break;

    case 'list_detections':
        $ch = curl_init("$CAMERA_SERVER/photos/detections");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $resp = curl_exec($ch);
        curl_close($ch);
        echo $resp ?: '[]';
        break;

    default:
        echo json_encode(['error' => 'Acțiune necunoscută']);
}
