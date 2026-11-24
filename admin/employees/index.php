<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$errors = [];
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi form tidak valid. Muat ulang halaman.';
    }
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Nama wajib diisi dan maksimal 100 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) $errors[] = 'Format email tidak valid.';
    if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'Email sudah digunakan akun lain.';
        } else {
            $insert = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'employee')");
            $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            setFlash('success', 'Akun employee berhasil dibuat. Berikan kredensial secara aman kepada petugas.');
            redirect('admin/employees/index.php');
        }
    }
}

$stmt = $pdo->query("SELECT id,name,email,created_at FROM users WHERE role='employee' ORDER BY created_at DESC");
$employees = $stmt->fetchAll();
$pageTitle = 'Manajemen Employee';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-4"><div class="eyebrow">STAFF ACCESS CONTROL</div><h1 class="h3 fw-bold">Manajemen Employee</h1><p class="text-secondary">Hanya Admin yang dapat membuat akun Employee. Employee tidak memiliki akses ke laporan atau manajemen akun staff.</p></div>
<div class="row g-4">
 <div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4 p-4"><h2 class="h5 fw-bold">Buat akun Employee</h2>
 <?php foreach($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
 <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
 <div class="mb-3"><label class="form-label" for="name">Nama lengkap</label><input class="form-control" id="name" name="name" maxlength="100" value="<?= e($name) ?>" required></div>
 <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" type="email" id="email" name="email" maxlength="150" value="<?= e($email) ?>" required></div>
 <div class="mb-3"><label class="form-label" for="password">Password sementara</label><input class="form-control" type="password" id="password" name="password" minlength="8" required autocomplete="new-password"><div class="form-text">Minimal 8 karakter. Sampaikan melalui kanal yang aman.</div></div>
 <button class="btn btn-komikin" type="submit">Buat Employee</button></form></div></div>
 <div class="col-lg-7"><div class="card border-0 shadow-sm rounded-4"><div class="p-3"><h2 class="h5 fw-bold mb-0">Daftar Employee</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Nama</th><th>Email</th><th>Bergabung</th></tr></thead><tbody>
 <?php if(!$employees): ?><tr><td colspan="3" class="text-center text-secondary py-4">Belum ada akun Employee.</td></tr><?php endif; ?>
 <?php foreach($employees as $employee): ?><tr><td class="fw-semibold"><?= e($employee['name']) ?></td><td><?= e($employee['email']) ?></td><td><?= e(date('d-m-Y', strtotime($employee['created_at']))) ?></td></tr><?php endforeach; ?>
 </tbody></table></div></div></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
