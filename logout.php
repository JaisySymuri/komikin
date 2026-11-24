<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('405 - Method Not Allowed');
}

$token = $_POST['csrf_token'] ?? null;
if (!verifyCsrfToken(is_string($token) ? $token : null)) {
    http_response_code(403);
    exit('403 - Invalid CSRF token');
}

logoutUser();
session_start();
setFlash('success', 'Anda berhasil logout.');
redirect('login.php');
