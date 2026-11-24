<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$id=(int)($_GET['id'] ?? 0);
$stmt=$pdo->prepare('SELECT c.*,cat.category_name FROM comics c LEFT JOIN categories cat ON cat.id=c.category_id WHERE c.id=?');
$stmt->execute([$id]);$comic=$stmt->fetch();
if(!$comic){http_response_code(404);exit('Komik tidak ditemukan.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
    requireCustomer();
    if(!verifyCsrfToken($_POST['csrf_token'] ?? null)){setFlash('danger','Sesi form tidak valid.');redirect('comic.php?id='.$id);}
    $qty=filter_var($_POST['quantity'] ?? null,FILTER_VALIDATE_INT);
    if($qty===false || $qty<1 || $qty>20){setFlash('danger','Jumlah sewa harus antara 1 sampai 20.');redirect('comic.php?id='.$id);}
    $check=$pdo->prepare('SELECT stock FROM comics WHERE id=?');$check->execute([$id]);$stock=(int)$check->fetchColumn();
    $cart=$_SESSION['rental_cart'] ?? [];
    $newQty=(int)($cart[$id] ?? 0)+(int)$qty;
    if($newQty>$stock){setFlash('danger','Jumlah di keranjang melebihi stok yang tersedia.');redirect('comic.php?id='.$id);}
    $cart[$id]=$newQty;$_SESSION['rental_cart']=$cart;
    setFlash('success','Komik ditambahkan ke keranjang sewa.');
    redirect('customer/cart.php');
}
$pageTitle=$comic['title'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="mb-3"><a href="<?= e(BASE_URL) ?>catalog.php" class="text-decoration-none">&larr; Kembali ke katalog</a></div>
<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4"><div class="row g-4 align-items-start"><div class="col-md-4"><?php if($comic['cover_image']): ?><img class="detail-cover" src="<?= e(BASE_URL.$comic['cover_image']) ?>" alt="Cover <?= e($comic['title']) ?>"><?php else: ?><div class="detail-cover cover-placeholder">KOMIKIN</div><?php endif; ?></div><div class="col-md-8"><span class="badge text-bg-light"><?= e($comic['category_name'] ?? 'Tanpa genre') ?></span><h1 class="display-6 fw-bold mt-2"><?= e($comic['title']) ?></h1><p class="text-secondary">Oleh <?= e($comic['author']) ?></p><div class="h4 fw-bold mb-1"><?= formatRupiah($comic['rental_price']) ?></div><div class="text-secondary small mb-4">Harga sewa per komik untuk <?= (int)DEFAULT_RENTAL_DAYS ?> hari</div><div class="mb-4"><?= nl2br(e($comic['description'] ?: 'Belum ada deskripsi untuk komik ini.')) ?></div><p class="<?= (int)$comic['stock']>0?'text-success':'text-danger' ?> fw-semibold"><?= (int)$comic['stock']>0?'Tersedia: '.(int)$comic['stock'].' eksemplar':'Stok sedang habis' ?></p>
<?php if(!isLoggedIn()): ?><a class="btn btn-komikin" href="<?= e(BASE_URL) ?>login.php">Login untuk menyewa</a><?php elseif(currentUser()['role']==='customer' && (int)$comic['stock']>0): ?><form method="post" class="d-flex gap-2 align-items-end"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div><label class="form-label small">Jumlah</label><input class="form-control" type="number" name="quantity" min="1" max="<?= min(20,(int)$comic['stock']) ?>" value="1" style="width:100px"></div><button class="btn btn-komikin">Tambah ke Keranjang</button></form><?php endif; ?>
</div></div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
