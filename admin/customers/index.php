<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdminOrEmployee();

$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.id,u.name,u.email,u.created_at,
        COUNT(DISTINCT r.id) AS rental_count,
        COALESCE(SUM(r.total_price),0) AS rental_total,
        SUM(CASE WHEN r.status='active' THEN 1 ELSE 0 END) AS active_count
        FROM users u
        LEFT JOIN rentals r ON r.user_id=u.id
        WHERE u.role='customer'";
$params = [];
if ($q !== '') {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sql .= " GROUP BY u.id,u.name,u.email,u.created_at ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Data Pelanggan';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-4">
 <div class="eyebrow">CUSTOMER MANAGEMENT</div>
 <h1 class="h3 fw-bold">Data Pelanggan</h1>
 <p class="text-secondary">Lihat akun pelanggan dan ringkasan aktivitas penyewaan.</p>
</div>
<form method="get" class="card border-0 shadow-sm rounded-4 p-3 mb-3">
 <div class="row g-2"><div class="col-md-10"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau email pelanggan"></div><div class="col-md-2 d-grid"><button class="btn btn-komikin">Cari</button></div></div>
</form>
<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
<table class="table align-middle mb-0">
<thead class="table-light"><tr><th>Pelanggan</th><th>Bergabung</th><th>Total transaksi</th><th>Transaksi aktif</th><th>Total nilai sewa</th><th></th></tr></thead>
<tbody>
<?php if (!$customers): ?><tr><td colspan="6" class="text-center text-secondary py-5">Pelanggan tidak ditemukan.</td></tr><?php endif; ?>
<?php foreach ($customers as $c): ?>
<tr>
 <td class="fw-semibold"><?= e($c['name']) ?><div class="small text-secondary"><?= e($c['email']) ?></div></td>
 <td><?= e(date('d-m-Y', strtotime($c['created_at']))) ?></td>
 <td><?= (int)$c['rental_count'] ?></td><td><?= (int)$c['active_count'] ?></td>
 <td><?= formatRupiah($c['rental_total']) ?></td>
 <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= e(BASE_URL) ?>admin/customers/detail.php?id=<?= (int)$c['id'] ?>">Detail</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
