<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautorizat']);
    exit;
}

header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$POZE_DIR = __DIR__ . '/poze_semafor';

switch ($action) {

    case 'delete_poza':
        $filename = basename($input['filename'] ?? '');
        if (!$filename) {
            echo json_encode(['error' => 'Filename invalid']);
            exit;
        }
        $full = $POZE_DIR . '/' . $filename;
        if (file_exists($full)) {
            unlink($full);
        }
        $db = getDB();
        $db->prepare("DELETE FROM numere_masini WHERE poza LIKE ?")->execute(['%' . $filename]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_numar':
        $id = intval($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['error' => 'ID invalid']);
            exit;
        }
        $db = getDB();
        $stmt = $db->prepare("SELECT poza FROM numere_masini WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && $row['poza'] && file_exists($row['poza'])) {
            unlink($row['poza']);
        }
        $db->prepare("DELETE FROM numere_masini WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_all_poze':
        $files = glob($POZE_DIR . '/*.jpg');
        foreach ($files as $f) { unlink($f); }
        $db = getDB();
        $db->exec("DELETE FROM numere_masini");
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Actiune necunoscuta']);
}
