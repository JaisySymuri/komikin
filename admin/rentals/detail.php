<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rental_helpers.php';
requireAdminOrEmployee();
$id=(int)($_GET['id'] ?? 0);
$stmt=$pdo->prepare('SELECT r.*,u.name AS customer_name,u.email FROM rentals r JOIN users u ON u.id=r.user_id WHERE r.id=?');
$stmt->execute([$id]);$rental=$stmt->fetch();
if(!$rental){http_response_code(404);exit('Transaksi tidak ditemukan.');}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verifyCsrfToken($_POST['csrf_token'] ?? null)){http_response_code(400);exit('Sesi form tidak valid.');}
    $detailId=(int)($_POST['detail_id'] ?? 0);
    $qty=filter_var($_POST['quantity'] ?? null,FILTER_VALIDATE_INT);
    if($qty===false || $qty<1){setFlash('danger','Jumlah pengembalian tidak valid.');redirect('admin/rentals/detail.php?id='.$id);}
    try{
        $pdo->beginTransaction();
        $lock=$pdo->prepare('SELECT rd.id,rd.quantity,rd.comic_id,r.due_date,r.status FROM rental_details rd JOIN rentals r ON r.id=rd.rental_id WHERE rd.id=? AND rd.rental_id=? FOR UPDATE');
        $lock->execute([$detailId,$id]);$line=$lock->fetch();
        if(!$line || $line['status']!=='active')throw new RuntimeException('Item transaksi tidak aktif atau tidak ditemukan.');
        $summary=getRentalItemReturnSummary($pdo,$detailId);
        $remaining=(int)$line['quantity']-$summary['returned_qty'];
        if($qty>$remaining)throw new RuntimeException('Jumlah pengembalian melebihi jumlah yang belum kembali ('.$remaining.').');
        $now=date('Y-m-d H:i:s');
        $fee=calculateLateFee($line['due_date'],$now,$qty);
        $insert=$pdo->prepare('INSERT INTO rental_item_returns (rental_detail_id,quantity,returned_at,late_fee) VALUES (?,?,?,?)');
        $insert->execute([$detailId,$qty,$now,$fee]);
        $stock=$pdo->prepare('UPDATE comics SET stock=stock+?,updated_at=NOW() WHERE id=?');
        $stock->execute([$qty,(int)$line['comic_id']]);
        $newReturned=$summary['returned_qty']+$qty;
        if($newReturned >= (int)$line['quantity']){
            $agg=getRentalItemReturnSummary($pdo,$detailId);
            $update=$pdo->prepare('UPDATE rental_details SET returned_at=?,late_fee=? WHERE id=?');
            $update->execute([$now,$agg['late_fee'],$detailId]);
        }
        $pending=$pdo->prepare("SELECT COUNT(*) FROM rental_details rd LEFT JOIN (SELECT rental_detail_id,SUM(quantity) AS returned_qty FROM rental_item_returns GROUP BY rental_detail_id) ret ON ret.rental_detail_id=rd.id WHERE rd.rental_id=? AND COALESCE(ret.returned_qty,0)<rd.quantity");
        $pending->execute([$id]);
        if((int)$pending->fetchColumn()===0){$done=$pdo->prepare("UPDATE rentals SET status='completed',updated_at=NOW() WHERE id=?");$done->execute([$id]);}
        $pdo->commit();
        setFlash('success','Pengembalian berhasil dicatat. Denda pengembalian ini: '.formatRupiah($fee));
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        setFlash('danger',APP_DEBUG?$e->getMessage():'Pengembalian gagal diproses.');
    }
    redirect('admin/rentals/detail.php?id='.$id);
}

$lines=$pdo->prepare("SELECT rd.*,c.title,c.author,COALESCE(ret.returned_qty,0) AS returned_qty,COALESCE(ret.late_fee,0) AS total_late_fee FROM rental_details rd JOIN comics c ON c.id=rd.comic_id LEFT JOIN (SELECT rental_detail_id,SUM(quantity) AS returned_qty,SUM(late_fee) AS late_fee FROM rental_item_returns GROUP BY rental_detail_id) ret ON ret.rental_detail_id=rd.id WHERE rd.rental_id=? ORDER BY rd.id");
$lines->execute([$id]);$details=$lines->fetchAll();
$returns=$pdo->prepare('SELECT rir.*,c.title FROM rental_item_returns rir JOIN rental_details rd ON rd.id=rir.rental_detail_id JOIN comics c ON c.id=rd.comic_id WHERE rd.rental_id=? ORDER BY rir.returned_at DESC');
$returns->execute([$id]);$returnRows=$returns->fetchAll();
$pageTitle='Transaksi #'.$id;
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-3"><a href="<?= e(BASE_URL) ?>admin/rentals/index.php" class="text-decoration-none">&larr; Daftar penyewaan</a></div>
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><div class="eyebrow">RENTAL #<?= $id ?></div><h1 class="h3 fw-bold"><?= e($rental['customer_name']) ?></h1><p class="text-secondary mb-0"><?= e($rental['email']) ?> · Jatuh tempo <?= e($rental['due_date']) ?></p></div><span class="badge <?= $rental['status']==='active'?'text-bg-primary':'text-bg-success' ?> fs-6 align-self-start"><?= e(ucfirst($rental['status'])) ?></span></div>
<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Komik</th><th>Qty</th><th>Kembali</th><th>Sisa</th><th>Denda</th><th>Proses pengembalian</th></tr></thead><tbody>
<?php foreach($details as $d): $remaining=(int)$d['quantity']-(int)$d['returned_qty']; ?><tr><td class="fw-semibold"><?= e($d['title']) ?><div class="small text-secondary"><?= e($d['author']) ?></div></td><td><?= (int)$d['quantity'] ?></td><td><?= (int)$d['returned_qty'] ?></td><td><?= $remaining ?></td><td><?= formatRupiah($d['total_late_fee']) ?></td><td><?php if($remaining>0 && $rental['status']==='active'): ?><form method="post" class="d-flex gap-2 align-items-center"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="detail_id" value="<?= (int)$d['id'] ?>"><input class="form-control form-control-sm" style="max-width:90px" type="number" name="quantity" min="1" max="<?= $remaining ?>" value="<?= $remaining ?>" required><button class="btn btn-sm btn-komikin">Catat Return</button></form><?php else: ?><span class="text-success small">Selesai</span><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="card border-0 shadow-sm rounded-4 p-4"><h2 class="h5 fw-bold">Riwayat pengembalian</h2><?php if(!$returnRows): ?><p class="text-secondary mb-0">Belum ada pengembalian tercatat.</p><?php else: ?><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Komik</th><th>Jumlah</th><th>Waktu</th><th>Denda</th></tr></thead><tbody><?php foreach($returnRows as $ret): ?><tr><td><?= e($ret['title']) ?></td><td><?= (int)$ret['quantity'] ?></td><td><?= e($ret['returned_at']) ?></td><td><?= formatRupiah($ret['late_fee']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
