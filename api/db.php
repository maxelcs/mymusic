<?php
// api/db.php — Connessione al database
define('DB_HOST', 'sql313.infinityfree.com');
define('DB_NAME', 'if0_41854328_mymusic');
define('DB_USER', 'if0_41854328');       // cambia se hai credenziali diverse
define('DB_PASS', 'Al3ss4ndr0012');           // cambia con la tua password XAMPP

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}
