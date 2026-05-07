<?php
// Dati del database forniti da Railway
define('DB_HOST', 'mysql.railway.internal');
define('DB_NAME', 'railway');
define('DB_USER', 'root');
define('DB_PASS', 'nqannNgcosaVWPutybQGEZyDQGzgNhVH');
define('DB_PORT', '3306');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Su Railway è importante specificare la porta 3306
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Questo ci aiuterà a capire se il database rifiuta la connessione
            header('Content-Type: application/json');
            die(json_encode(['ok' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
