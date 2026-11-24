<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAdminOrEmployee();
$comicCount=(int)$pdo->query('SELECT COUNT(*) FROM comics')->fetchColumn();
$customerCount=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$activeRentalCount=(int)$pdo->query("SELECT COUNT(*) FROM rentals WHERE status='active'")->fetchColumn();
$overdueCount=(int)$pdo->query("SELECT COUNT(DISTINCT r.id) FROM rentals r JOIN rental_details rd ON rd.rental_id=r.id LEFT JOIN (SELECT rental_detail_id,SUM(quantity) AS returned_qty FROM rental_item_returns GROUP BY rental_detail_id) ret ON ret.rental_detail_id=rd.id WHERE r.status='active' AND r.due_date<CURDATE() AND COALESCE(ret.returned_qty,0)<rd.quantity")->fetchColumn();
$pageTitle='Admin Dashboard';require __DIR__.'/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4"><div><span class="eyebrow">AREA <?= currentUser()['role']==='admin'?'ADMINISTRATOR':'EMPLOYEE' ?></span><h1 class="h2 fw-bold mt-1">Dashboard</h1><p class="text-secondary mb-0">Ringkasan operasional KOMIKIN.</p></div></div>
<div class="row g-3"><?php foreach([['Total Komik',$comicCount],['Pelanggan',$customerCount],['Penyewaan Aktif',$activeRentalCount],['Transaksi Terlambat',$overdueCount]] as [$label,$value]): ?><div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="text-secondary small"><?= e($label) ?></div><div class="display-6 fw-bold"><?= (int)$value ?></div></div></div><?php endforeach; ?></div>
<div class="row g-3 mt-2">
 <div class="col-md-4"><a class="text-decoration-none" href="<?= e(BASE_URL) ?>admin/comics/index.php"><div class="card border-0 shadow-sm rounded-4 p-4 h-100"><h2 class="h5 fw-bold">Inventaris Komik</h2><p class="text-secondary mb-0">Kelola cover, harga, genre, dan stok.</p></div></a></div>
 <div class="col-md-4"><a class="text-decoration-none" href="<?= e(BASE_URL) ?>admin/customers/index.php"><div class="card border-0 shadow-sm rounded-4 p-4 h-100"><h2 class="h5 fw-bold">Data Pelanggan</h2><p class="text-secondary mb-0">Lihat akun dan riwayat transaksi pelanggan.</p></div></a></div>
 <?php if(currentUser()['role']==='admin'): ?><div class="col-md-4"><a class="text-decoration-none" href="<?= e(BASE_URL) ?>admin/reports/index.php"><div class="card border-0 shadow-sm rounded-4 p-4 h-100"><h2 class="h5 fw-bold">Laporan</h2><p class="text-secondary mb-0">Analisis penyewaan, denda, dan stok.</p></div></a></div><div class="col-md-4"><a class="text-decoration-none" href="<?= e(BASE_URL) ?>admin/employees/index.php"><div class="card border-0 shadow-sm rounded-4 p-4 h-100"><h2 class="h5 fw-bold">Manajemen Employee</h2><p class="text-secondary mb-0">Buat dan lihat akun petugas.</p></div></a></div><?php endif; ?>
</div>
<div class="mt-3"><a class="btn btn-komikin" href="<?= e(BASE_URL) ?>admin/rentals/index.php">Kelola Penyewaan</a></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
