<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Beranda';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-panel">
    <span class="eyebrow">SEWA KOMIK JADI LEBIH MUDAH</span>
    <h1 class="display-5 fw-bold mt-2">Cerita seru, tinggal pilih.</h1>
    <p class="lead mt-3">Temukan komik favoritmu dan nikmati pengalaman membaca bersama KOMIKIN.</p>
    <?php if (!isLoggedIn()): ?>
        <a class="btn btn-komikin btn-lg mt-2" href="<?= e(BASE_URL) ?>register.php">Mulai Sekarang</a>
        <a class="btn btn-outline-light btn-lg mt-2 ms-2" href="<?= e(BASE_URL) ?>login.php">Login</a>
    <?php elseif (in_array(currentUser()['role'], ['admin', 'employee'], true)): ?>
        <a class="btn btn-komikin btn-lg mt-2" href="<?= e(BASE_URL) ?>admin/index.php">Buka Dashboard</a>
    <?php else: ?>
        <a class="btn btn-komikin btn-lg mt-2" href="<?= e(BASE_URL) ?>customer/index.php">Akun Saya</a>
    <?php endif; ?>
</section>
<section class="py-5">
    <div class="row g-3">
        <div class="col-md-4"><div class="stat-card h-100"><h2 class="h5 fw-bold">Pilih Komik</h2><p class="text-secondary mb-0">Jelajahi koleksi komik dari berbagai genre.</p></div></div>
        <div class="col-md-4"><div class="stat-card h-100"><h2 class="h5 fw-bold">Sewa Mudah</h2><p class="text-secondary mb-0">Proses penyewaan yang sederhana dan praktis.</p></div></div>
        <div class="col-md-4"><div class="stat-card h-100"><h2 class="h5 fw-bold">Baca & Kembalikan</h2><p class="text-secondary mb-0">Pantau masa sewa dan riwayat transaksi.</p></div></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
