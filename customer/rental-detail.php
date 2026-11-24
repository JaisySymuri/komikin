<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireCustomer();
$id=(int)($_GET['id'] ?? 0);
$stmt=$pdo->prepare('SELECT * FROM rentals WHERE id=? AND user_id=?');
$stmt->execute([$id,(int)currentUser()['id']]); $rental=$stmt->fetch();
if(!$rental){http_response_code(404);exit('Transaksi tidak ditemukan.');}
$stmt=$pdo->prepare("SELECT rd.*,c.title,c.author,c.cover_image,COALESCE(ret.returned_qty,0) AS returned_qty,COALESCE(ret.late_fee,0) AS calculated_late_fee FROM rental_details rd JOIN comics c ON c.id=rd.comic_id LEFT JOIN (SELECT rental_detail_id,SUM(quantity) AS returned_qty,SUM(late_fee) AS late_fee FROM rental_item_returns GROUP BY rental_detail_id) ret ON ret.rental_detail_id=rd.id WHERE rd.rental_id=? ORDER BY rd.id");
$stmt->execute([$id]);$details=$stmt->fetchAll();
$returnsStmt=$pdo->prepare('SELECT rir.*,c.title FROM rental_item_returns rir JOIN rental_details rd ON rd.id=rir.rental_detail_id JOIN comics c ON c.id=rd.comic_id WHERE rd.rental_id=? ORDER BY rir.returned_at DESC');
$returnsStmt->execute([$id]);$returns=$returnsStmt->fetchAll();
$pageTitle='Detail Penyewaan #'.$id;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><a href="<?= e(BASE_URL) ?>customer/rentals.php" class="text-decoration-none">&larr; Riwayat penyewaan</a></div>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><div class="eyebrow">RENTAL DETAIL</div><h1 class="h3 fw-bold">Transaksi #<?= $id ?></h1><p class="text-secondary mb-0">Tanggal sewa: <?= e($rental['rental_date']) ?> · Jatuh tempo: <?= e($rental['due_date']) ?></p></div><span class="badge <?= $rental['status']==='active'?'text-bg-primary':'text-bg-success' ?> fs-6"><?= e(ucfirst($rental['status'])) ?></span></div>
<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Komik</th><th>Harga</th><th>Jumlah</th><th>Kembali</th><th>Denda</th></tr></thead><tbody><?php foreach($details as $d): ?><tr><td class="fw-semibold"><?= e($d['title']) ?><div class="small text-secondary"><?= e($d['author']) ?></div></td><td><?= formatRupiah($d['price']) ?></td><td><?= (int)$d['quantity'] ?></td><td><?= (int)$d['returned_qty'] ?>/<?= (int)$d['quantity'] ?></td><td><?= formatRupiah($d['calculated_late_fee']) ?></td></tr><?php endforeach; ?></tbody></table></div><div class="p-3 text-end"><span class="text-secondary me-2">Total sewa</span><strong class="h5"><?= formatRupiah($rental['total_price']) ?></strong></div></div>
<div class="card border-0 shadow-sm rounded-4 p-4"><h2 class="h5 fw-bold">Riwayat pengembalian</h2><?php if(!$returns): ?><p class="text-secondary mb-0">Belum ada komik yang dikembalikan.</p><?php else: ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Komik</th><th>Jumlah</th><th>Waktu kembali</th><th>Denda</th></tr></thead><tbody><?php foreach($returns as $ret): ?><tr><td><?= e($ret['title']) ?></td><td><?= (int)$ret['quantity'] ?></td><td><?= e($ret['returned_at']) ?></td><td><?= formatRupiah($ret['late_fee']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
