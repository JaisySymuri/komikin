<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function currentUser(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['user']['role'] ?? null) !== $role) {
        http_response_code(403);
        exit('403 - Anda tidak memiliki akses ke halaman ini.');
    }
}

function requireAdmin(): void
{
    requireRole('admin');
}

function requireAdminOrEmployee(): void
{
    requireLogin();
    if (!in_array($_SESSION['user']['role'] ?? null, ['admin', 'employee'], true)) {
        http_response_code(403);
        exit('403 - Halaman ini hanya untuk Admin atau Employee.');
    }
}

function requireCustomer(): void
{
    requireRole('customer');
}

function isStaff(): bool
{
    return isLoggedIn() && in_array(currentUser()['role'] ?? null, ['admin', 'employee'], true);
}

function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
}

function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
