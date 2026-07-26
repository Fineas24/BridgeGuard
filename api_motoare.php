<?php
session_start();
if (!isset($_SESSION['user'])) { http_response_code(401); exit; }

header('Content-Type: application/json');

$python_url = 'http://127.0.0.1:5002';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ch = curl_init("$python_url/status");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(['connected' => false, 'error' => 'Serverul Python nu rulează']);
    } else {
        echo $resp;
    }
    curl_close($ch);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $ch = curl_init("$python_url/send");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        echo json_encode(['ok' => false, 'error' => 'Serverul Python nu răspunde']);
    } else {
        echo $resp;
    }
    curl_close($ch);
    exit;
}
