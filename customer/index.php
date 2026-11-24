<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireCustomer();
$stmt=$pdo->prepare('SELECT COUNT(*) FROM rentals WHERE user_id=?');$stmt->execute([(int)currentUser()['id']]);$rentalCount=(int)$stmt->fetchColumn();
$stmt=$pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id=? AND status='active'");$stmt->execute([(int)currentUser()['id']]);$activeCount=(int)$stmt->fetchColumn();
$pageTitle='Akun Saya';require __DIR__.'/../includes/header.php';
?>
<div class="hero-panel"><span class="eyebrow">AREA PELANGGAN</span><h1 class="display-6 fw-bold mt-2">Halo, <?= e(currentUser()['name']) ?>!</h1><p class="mb-0">Selamat datang di KOMIKIN. Siap menemukan komik untuk dibaca?</p><div class="mt-3 d-flex flex-wrap gap-2"><a class="btn btn-komikin" href="<?= e(BASE_URL) ?>catalog.php">Cari Komik</a><a class="btn btn-outline-light" href="<?= e(BASE_URL) ?>customer/cart.php">Keranjang Sewa</a><a class="btn btn-outline-light" href="<?= e(BASE_URL) ?>customer/rentals.php">Riwayat Penyewaan</a></div></div>
<div class="row g-3 mt-2"><div class="col-md-4"><div class="stat-card"><div class="text-secondary small">Total Transaksi</div><div class="display-6 fw-bold"><?= $rentalCount ?></div></div></div><div class="col-md-4"><div class="stat-card"><div class="text-secondary small">Penyewaan Aktif</div><div class="display-6 fw-bold"><?= $activeCount ?></div></div></div></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
