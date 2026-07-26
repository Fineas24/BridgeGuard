<?php
session_start();
if (!isset($_SESSION['user'])) { http_response_code(401); exit; }

header('Content-Type: application/json');

$pico_ip   = '192.168.100.61';
$pico_port = 5008;

function udp_trimite($ip, $port, $msg, $asteapta_raspuns = false) {
    $sock = @fsockopen("udp://$ip", $port, $errno, $errstr, 1);
    if (!$sock) return null;
    fwrite($sock, $msg);
    $raspuns = null;
    if ($asteapta_raspuns) {
        stream_set_timeout($sock, 0, 400000);
        $raspuns = fread($sock, 16);
    }
    fclose($sock);
    return $raspuns;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pong = udp_trimite($pico_ip, $pico_port, 'ping', true);
    echo json_encode([
        'connected' => ($pong === 'pong'),
        'error'     => ($pong === 'pong') ? null : 'Robot neconectat'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $harta = [
        'fata'    => 'w',
        'spate'   => 's',
        'stanga'  => 'a',
        'dreapta' => 'd',
        'stop'    => 'x',
        'pompa'   => 'p',
    ];
    $cmd = $input['command'] ?? '';
    if (!isset($harta[$cmd])) {
        echo json_encode(['ok' => false, 'error' => 'Comanda necunoscuta']);
        exit;
    }
    udp_trimite($pico_ip, $pico_port, $harta[$cmd]);
    echo json_encode(['ok' => true]);
    exit;
}
