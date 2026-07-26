<?php

require_once __DIR__ . '/config.local.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            die(json_encode(['error' => 'Conexiunea la baza de date a eșuat.']));
        }
    }
    return $pdo;
}
?>
