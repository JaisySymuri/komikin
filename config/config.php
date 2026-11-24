<?php
declare(strict_types=1);

/*
 * KOMIKIN configuration.
 * Before publishing, set APP_ENV to production and BASE_URL to the exact
 * HTTPS address shown in your InfinityFree control panel.
 */

define('APP_NAME', 'KOMIKIN');
// define('APP_ENV', 'development'); // Change to production before deployment.
define('APP_ENV', 'production'); // Change to production before deployment.
define('APP_DEBUG', APP_ENV === 'development');

// Keep the trailing slash. Example: https://your-domain.infinityfreeapp.com/
define('BASE_URL', 'http://localhost/rental-komik/');

define('DEFAULT_RENTAL_DAYS', 7);
define('LATE_FEE_PER_DAY', 2000.00);
define('CURRENCY', 'IDR');

define('MAX_COVER_UPLOAD_BYTES', 2 * 1024 * 1024); // 2 MB
define('COVER_UPLOAD_DIR', dirname(__DIR__) . '/uploads/comic-covers/');

// Harden PHP sessions and browser-facing response headers.
$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('KOMIKINSESSID');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if ($isHttps && APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

date_default_timezone_set('Asia/Jakarta');
