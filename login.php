<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(in_array(currentUser()['role'], ['admin', 'employee'], true) ? 'admin/index.php' : 'customer/index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!verifyCsrfToken(is_string($token) ? $token : null)) {
        $error = 'Sesi formulir tidak valid. Silakan muat ulang halaman.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Email atau password tidak valid.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            redirect(in_array($user['role'], ['admin', 'employee'], true) ? 'admin/index.php' : 'customer/index.php');
        }

        $error = 'Email atau password salah.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="auth-card">
            <span class="eyebrow">SELAMAT DATANG KEMBALI</span>
            <h1 class="h3 fw-bold mt-2">Login ke KOMIKIN</h1>
            <p class="text-secondary">Masuk untuk mengelola akun dan penyewaan komik.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="username">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button class="btn btn-komikin w-100" type="submit">Login</button>
            </form>
            <p class="text-center text-secondary small mt-4 mb-0">Belum punya akun? <a href="<?= e(BASE_URL) ?>register.php">Daftar di sini</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
