<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautorizat']);
    exit;
}

header('Content-Type: application/json');

$BASE = realpath(__DIR__ . '/uploads');
if (!$BASE) {
    mkdir(__DIR__ . '/uploads', 0775, true);
    mkdir(__DIR__ . '/uploads/captures', 0775, true);
    mkdir(__DIR__ . '/uploads/detections', 0775, true);
    $BASE = realpath(__DIR__ . '/uploads');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function safePath(string $base, string $rel): ?string {
    $rel = str_replace('..', '', $rel);
    $rel = ltrim($rel, '/');
    $full = realpath($base . '/' . $rel);
    if (!$full) {
        $parent = realpath($base . '/' . dirname($rel));
        if (!$parent || strpos($parent, $base) !== 0) return null;
        return $parent . '/' . basename($rel);
    }
    if (strpos($full, $base) !== 0) return null;
    return $full;
}

function relPath(string $base, string $full): string {
    return ltrim(str_replace($base, '', $full), '/');
}

switch ($action) {

    case 'list':
        $dir = $_GET['path'] ?? '';
        $full = $dir ? safePath($BASE, $dir) : $BASE;
        if (!$full || !is_dir($full)) {
            echo json_encode(['error' => 'Folder invalid']);
            exit;
        }

        $folders = [];
        $files = [];

        foreach (scandir($full) as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $full . '/' . $item;
            $rel = relPath($BASE, $itemPath);

            if (is_dir($itemPath)) {
                $count = count(glob($itemPath . '/*.{jpg,jpeg,png}', GLOB_BRACE));
                $folders[] = [
                    'name' => $item,
                    'path' => $rel,
                    'count' => $count
                ];
            } else if (preg_match('/\.(jpg|jpeg|png)$/i', $item)) {
                $stat = stat($itemPath);
                $files[] = [
                    'name' => $item,
                    'path' => $rel,
                    'url' => 'uploads/' . $rel,
                    'size' => $stat['size'],
                    'date' => date('d/m/Y — H:i', $stat['mtime'])
                ];
            }
        }

        usort($folders, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        usort($files, fn($a, $b) => strcmp($b['name'], $a['name']));

        $breadcrumb = [];
        if ($dir) {
            $parts = explode('/', $dir);
            $cumul = '';
            foreach ($parts as $p) {
                $cumul = $cumul ? $cumul . '/' . $p : $p;
                $breadcrumb[] = ['name' => $p, 'path' => $cumul];
            }
        }

        echo json_encode([
            'current' => $dir ?: '',
            'breadcrumb' => $breadcrumb,
            'folders' => $folders,
            'files' => $files
        ]);
        break;

    case 'create_folder':
        $input = json_decode(file_get_contents('php://input'), true);
        $parent = $input['parent'] ?? '';
        $name = preg_replace('/[^a-zA-Z0-9_\-\s()ăîâșțĂÎÂȘȚ]/u', '', $input['name'] ?? '');

        if (!$name) {
            echo json_encode(['error' => 'Nume invalid']);
            exit;
        }

        $parentFull = $parent ? safePath($BASE, $parent) : $BASE;
        if (!$parentFull || !is_dir($parentFull)) {
            echo json_encode(['error' => 'Folder părinte invalid']);
            exit;
        }

        $newPath = $parentFull . '/' . $name;
        if (file_exists($newPath)) {
            echo json_encode(['error' => 'Folderul există deja']);
            exit;
        }

        mkdir($newPath, 0777, true);
        chmod($newPath, 0777);
        echo json_encode(['success' => true, 'path' => relPath($BASE, $newPath)]);
        break;

    case 'move':
        $input = json_decode(file_get_contents('php://input'), true);
        $files = $input['files'] ?? [];
        $dest = $input['destination'] ?? '';

        $destFull = $dest ? safePath($BASE, $dest) : $BASE;
        if (!$destFull || !is_dir($destFull)) {
            echo json_encode(['error' => 'Destinație invalidă']);
            exit;
        }

        $moved = 0;
        foreach ($files as $f) {
            $src = safePath($BASE, $f);
            if (!$src || !file_exists($src) || is_dir($src)) continue;
            $target = $destFull . '/' . basename($src);
            if (rename($src, $target)) $moved++;
        }

        echo json_encode(['success' => true, 'moved' => $moved]);
        break;

    case 'delete_folder':
        $input = json_decode(file_get_contents('php://input'), true);
        $path = $input['path'] ?? '';
        $full = safePath($BASE, $path);

        if (!$full || !is_dir($full) || $full === $BASE) {
            echo json_encode(['error' => 'Nu se poate șterge']);
            exit;
        }

        $rel = relPath($BASE, $full);
        if ($rel === 'captures' || $rel === 'detections') {
            echo json_encode(['error' => 'Folderele implicite nu pot fi șterse']);
            exit;
        }

        $items = array_diff(scandir($full), ['.', '..']);
        if (count($items) > 0) {
            echo json_encode(['error' => 'Folderul nu e gol']);
            exit;
        }

        rmdir($full);
        echo json_encode(['success' => true]);
        break;

    case 'delete_file':
        $input = json_decode(file_get_contents('php://input'), true);
        $path = $input['path'] ?? '';
        $full = safePath($BASE, $path);

        if (!$full || !file_exists($full) || is_dir($full)) {
            echo json_encode(['error' => 'Fișier invalid']);
            exit;
        }

        unlink($full);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Acțiune necunoscută']);
}
