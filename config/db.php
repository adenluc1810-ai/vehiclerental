<?php
// ===== Database Configuration =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'vrms');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ===== Base URL =====
// Auto-detected so the app works whether it lives at the domain root
// (http://localhost/) or in a sub-folder (http://localhost/vehiclerental/).
// Set BASE_URL_OVERRIDE below only if auto-detection ever guesses wrong.
$BASE_URL_OVERRIDE = ''; // e.g. '/vehiclerental/'

if ($BASE_URL_OVERRIDE !== '') {
    define('BASE_URL', '/' . trim($BASE_URL_OVERRIDE, '/') . '/');
} else {
    // Project root = the folder that contains config/
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $docRoot     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    $base = '/';
    if ($docRoot !== '' && strpos($projectRoot, $docRoot) === 0) {
        $base = rtrim(substr($projectRoot, strlen($docRoot)), '/') . '/';
    }
    if ($base === '' || $base[0] !== '/') $base = '/' . ltrim($base, '/');
    define('BASE_URL', $base);
}
