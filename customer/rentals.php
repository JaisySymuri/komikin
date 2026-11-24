<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireCustomer();
$stmt=$pdo->prepare("SELECT r.id,r.rental_date,r.due_date,r.status,r.total_price,COALESCE(SUM(rd.quantity),0) AS item_count,COALESCE(SUM(COALESCE(ret.returned_qty,0)),0) AS returned_count FROM rentals r LEFT JOIN rental_details rd ON rd.rental_id=r.id LEFT JOIN (SELECT rental_detail_id,SUM(quantity) AS returned_qty FROM rental_item_returns GROUP BY rental_detail_id) ret ON ret.rental_detail_id=rd.id WHERE r.user_id=? GROUP BY r.id,r.rental_date,r.due_date,r.status,r.total_price ORDER BY r.id DESC");
$stmt->execute([(int)currentUser()['id']]); $rentals=$stmt->fetchAll();
$pageTitle='Riwayat Penyewaan';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="mb-4"><div class="eyebrow">YOUR RENTALS</div><h1 class="h3 fw-bold">Riwayat Penyewaan</h1><p class="text-secondary">Pantau transaksi, tanggal jatuh tempo, dan pengembalian komik.</p></div>
<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Kode</th><th>Tanggal sewa</th><th>Jatuh tempo</th><th>Item</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
<?php if(!$rentals): ?><tr><td colspan="7" class="text-center text-secondary py-5">Belum ada transaksi penyewaan.</td></tr><?php endif; ?>
<?php foreach($rentals as $r): $overdue=$r['status']==='active' && $r['due_date']<date('Y-m-d'); ?><tr><td class="fw-semibold">#<?= (int)$r['id'] ?></td><td><?= e($r['rental_date']) ?></td><td><?= e($r['due_date']) ?><?php if($overdue): ?><span class="badge text-bg-danger ms-1">Terlambat</span><?php endif; ?></td><td><?= (int)$r['returned_count'] ?>/<?= (int)$r['item_count'] ?> kembali</td><td><?= formatRupiah($r['total_price']) ?></td><td><span class="badge <?= $r['status']==='active'?'text-bg-primary':'text-bg-success' ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><a class="btn btn-sm btn-outline-dark" href="<?= e(BASE_URL) ?>customer/rental-detail.php?id=<?= (int)$r['id'] ?>">Detail</a></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
