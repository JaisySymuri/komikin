<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/*
 * Local Laragon defaults. For InfinityFree, replace these values with the
 * exact MySQL host, database name, username and password shown in its panel.
 * Do not use localhost for the InfinityFree database host.
 */
if (APP_ENV === 'production') {
    $dbHost = 'sql213.infinityfree.com';
    $dbPort = '3306';
    $dbName = 'if0_43487659_test';
    $dbUser = 'if0_43487659';
    $dbPass = 'RznbxVQgWaQry';
} else {
    $dbHost = '127.0.0.1';
    $dbPort = '3306';
    $dbName = 'rental_komik';
    $dbUser = 'root';
    $dbPass = '';
}

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    error_log('[KOMIKIN database] ' . $e->getMessage());
    http_response_code(500);
    if (APP_DEBUG) {
        exit('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    exit('A database error occurred. Please try again later.');
}
