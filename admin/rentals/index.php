<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdminOrEmployee();
$status=$_GET['status'] ?? 'all';
$sql="SELECT r.*,u.name AS customer_name,u.email,COALESCE(SUM(rd.quantity),0) AS item_count,COALESCE(SUM(COALESCE(ret.returned_qty,0)),0) AS returned_count FROM rentals r JOIN users u ON u.id=r.user_id LEFT JOIN rental_details rd ON rd.rental_id=r.id LEFT JOIN (SELECT rental_detail_id,SUM(quantity) AS returned_qty FROM rental_item_returns GROUP BY rental_detail_id) ret ON ret.rental_detail_id=rd.id";
$params=[];
if(in_array($status,['active','completed'],true)){$sql.=' WHERE r.status=?';$params[]=$status;}
$sql.=' GROUP BY r.id,u.name,u.email ORDER BY r.id DESC';
$stmt=$pdo->prepare($sql);$stmt->execute($params);$rentals=$stmt->fetchAll();
$pageTitle='Kelola Penyewaan';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-4"><div class="eyebrow">TRANSACTION MANAGEMENT</div><h1 class="h3 fw-bold">Kelola Penyewaan</h1><p class="text-secondary">Pantau transaksi dan proses pengembalian komik.</p></div>
<div class="d-flex gap-2 mb-3"><a class="btn btn-sm <?= $status==='all'?'btn-dark':'btn-outline-dark' ?>" href="?status=all">Semua</a><a class="btn btn-sm <?= $status==='active'?'btn-dark':'btn-outline-dark' ?>" href="?status=active">Aktif</a><a class="btn btn-sm <?= $status==='completed'?'btn-dark':'btn-outline-dark' ?>" href="?status=completed">Selesai</a></div>
<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Kode</th><th>Pelanggan</th><th>Tanggal</th><th>Jatuh tempo</th><th>Item kembali</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody><?php if(!$rentals): ?><tr><td colspan="8" class="text-center text-secondary py-5">Belum ada transaksi.</td></tr><?php endif; ?><?php foreach($rentals as $r): ?><tr><td class="fw-semibold">#<?= (int)$r['id'] ?></td><td><?= e($r['customer_name']) ?><div class="small text-secondary"><?= e($r['email']) ?></div></td><td><?= e($r['rental_date']) ?></td><td><?= e($r['due_date']) ?><?php if($r['status']==='active' && $r['due_date']<date('Y-m-d')): ?><span class="badge text-bg-danger ms-1">Terlambat</span><?php endif; ?></td><td><?= (int)$r['returned_count'] ?>/<?= (int)$r['item_count'] ?></td><td><?= formatRupiah($r['total_price']) ?></td><td><span class="badge <?= $r['status']==='active'?'text-bg-primary':'text-bg-success' ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><a class="btn btn-sm btn-outline-dark" href="<?= e(BASE_URL) ?>admin/rentals/detail.php?id=<?= (int)$r['id'] ?>">Detail / Return</a></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
