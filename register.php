<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect(in_array(currentUser()['role'], ['admin', 'employee'], true) ? 'admin/index.php' : 'customer/index.php');
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!verifyCsrfToken(is_string($token) ? $token : null)) {
        $errors[] = 'Sesi formulir tidak valid. Silakan muat ulang halaman.';
    }
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Nama wajib diisi dan maksimal 100 karakter.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors[] = 'Format email tidak valid.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain.';
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)'
            );
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'customer',
            ]);

            setFlash('success', 'Registrasi berhasil. Silakan login.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Daftar Akun';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="auth-card">
            <span class="eyebrow">GABUNG BERSAMA KAMI</span>
            <h1 class="h3 fw-bold mt-2">Buat akun KOMIKIN</h1>
            <p class="text-secondary">Daftar untuk mulai menyewa komik favoritmu.</p>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="mb-3">
                    <label class="form-label" for="name">Nama lengkap</label>
                    <input class="form-control" id="name" name="name" maxlength="100" value="<?= e($name) ?>" required autocomplete="name">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="150" value="<?= e($email) ?>" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                    <div class="form-text">Minimal 8 karakter.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="confirm_password">Konfirmasi password</label>
                    <input class="form-control" type="password" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password">
                </div>
                <button class="btn btn-komikin w-100" type="submit">Daftar Sekarang</button>
            </form>
            <p class="text-center text-secondary small mt-4 mb-0">Sudah punya akun? <a href="<?= e(BASE_URL) ?>login.php">Login</a></p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
