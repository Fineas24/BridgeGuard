<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautorizat']);
    exit;
}

header('Content-Type: application/json');

$BASE = realpath(__DIR__ . '/uploads');
if (!$BASE) { echo json_encode(['error' => 'Upload dir missing']); exit; }

$dest = $_POST['destination'] ?? '';
$dest = str_replace('..', '', $dest);

$destFull = $dest ? realpath($BASE . '/' . $dest) : $BASE;
if (!$destFull || strpos($destFull, $BASE) !== 0 || !is_dir($destFull)) {
    echo json_encode(['error' => 'Destinație invalidă']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Eroare la upload']);
    exit;
}

$mime = mime_content_type($_FILES['file']['tmp_name']);
if (!in_array($mime, ['image/jpeg', 'image/png'])) {
    echo json_encode(['error' => 'Doar JPG/PNG permise']);
    exit;
}

$ext = ($mime === 'image/png') ? 'png' : 'jpg';
$name = date('d-m-Y___H-i') . '.' . $ext;

$target = $destFull . '/' . $name;
$i = 1;
while (file_exists($target)) {
    $target = $destFull . '/' . date('d-m-Y___H-i') . "_$i." . $ext;
    $i++;
}

move_uploaded_file($_FILES['file']['tmp_name'], $target);
chmod($target, 0664);

echo json_encode(['success' => true, 'filename' => basename($target)]);
