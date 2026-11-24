<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdminOrEmployee();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT id,name,email,created_at FROM users WHERE id=? AND role='customer'");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { http_response_code(404); exit('Pelanggan tidak ditemukan.'); }

$stmt = $pdo->prepare("SELECT r.id,r.rental_date,r.due_date,r.status,r.total_price,
    COALESCE(SUM(rd.quantity),0) AS quantity
    FROM rentals r LEFT JOIN rental_details rd ON rd.rental_id=r.id
    WHERE r.user_id=? GROUP BY r.id,r.rental_date,r.due_date,r.status,r.total_price
    ORDER BY r.id DESC");
$stmt->execute([$id]);
$rentals = $stmt->fetchAll();
$pageTitle = 'Detail Pelanggan';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-3"><a href="<?= e(BASE_URL) ?>admin/customers/index.php" class="text-decoration-none">&larr; Data pelanggan</a></div>
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
 <div class="eyebrow">CUSTOMER PROFILE</div><h1 class="h3 fw-bold mt-1"><?= e($customer['name']) ?></h1>
 <p class="text-secondary mb-1"><?= e($customer['email']) ?></p><small class="text-secondary">Terdaftar sejak <?= e(date('d F Y', strtotime($customer['created_at']))) ?></small>
</div>
<div class="card border-0 shadow-sm rounded-4"><div class="p-3"><h2 class="h5 fw-bold mb-0">Riwayat Penyewaan</h2></div>
<div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Kode</th><th>Tanggal</th><th>Jatuh tempo</th><th>Jumlah komik</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
<?php if (!$rentals): ?><tr><td colspan="7" class="text-center text-secondary py-5">Belum ada transaksi.</td></tr><?php endif; ?>
<?php foreach ($rentals as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><?= e($r['rental_date']) ?></td><td><?= e($r['due_date']) ?></td><td><?= (int)$r['quantity'] ?></td><td><?= formatRupiah($r['total_price']) ?></td><td><span class="badge <?= $r['status']==='active'?'text-bg-primary':'text-bg-success' ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><a class="btn btn-sm btn-outline-dark" href="<?= e(BASE_URL) ?>admin/rentals/detail.php?id=<?= (int)$r['id'] ?>">Detail</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
