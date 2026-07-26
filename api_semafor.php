<?php
header('Content-Type: application/json');

$file = __DIR__ . '/Python/semafor_status.json';

if (!file_exists($file)) {
    echo json_encode(['culoare' => 'offline', 'detectat' => false, 'greutate' => 0, 'mesaj' => '', 'timestamp' => 0]);
    exit;
}

$data = json_decode(file_get_contents($file), true);

if (time() - ($data['timestamp'] ?? 0) > 10) {
    $data['culoare'] = 'offline';
}

$data['greutate'] = $data['greutate'] ?? 0;
$data['mesaj'] = $data['mesaj'] ?? '';

echo json_encode($data);
