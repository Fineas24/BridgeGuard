<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$GYRO_FILE = '/var/www/html/proiect/gyro_latest.json';
$STALE_SECONDS = 15; // daca datele sunt mai vechi de 15s, senzorul e offline

if (!file_exists($GYRO_FILE)) {
    echo json_encode([
        'ok'             => false,
        'online'         => false,
        'fata_spate'     => null,
        'stanga_dreapta' => null,
        'error'          => 'Nicio data primita inca de la Pico.'
    ]);
    exit;
}

$raw  = file_get_contents($GYRO_FILE);
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['ok' => false, 'online' => false, 'error' => 'Date invalide.']);
    exit;
}

$age    = time() - (int)($data['timestamp'] ?? 0);
$online = ($age < $STALE_SECONDS);

echo json_encode([
    'ok'             => true,
    'online'         => $online,
    'fata_spate'     => $data['fata_spate']     ?? null,
    'stanga_dreapta' => $data['stanga_dreapta'] ?? null,
    'age_seconds'    => $age
]);
