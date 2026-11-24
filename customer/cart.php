<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rental_helpers.php';
requireCustomer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Sesi form tidak valid. Silakan ulangi.');
        redirect('customer/cart.php');
    }
    $action = $_POST['action'] ?? '';
    $comicId = (int)($_POST['comic_id'] ?? 0);
    $cart = getCart();

    if ($action === 'remove') {
        unset($cart[$comicId]);
        $_SESSION['rental_cart'] = $cart;
        setFlash('success', 'Komik dihapus dari keranjang.');
        redirect('customer/cart.php');
    }

    if ($action === 'update') {
        $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 1 || $quantity > 20) {
            setFlash('danger', 'Jumlah harus antara 1 sampai 20.');
            redirect('customer/cart.php');
        }
        $stmt = $pdo->prepare('SELECT stock FROM comics WHERE id=?');
        $stmt->execute([$comicId]);
        $stock = $stmt->fetchColumn();
        if ($stock === false || $quantity > (int)$stock) {
            setFlash('danger', 'Jumlah melebihi stok yang tersedia.');
        } else {
            $cart[$comicId] = $quantity;
            $_SESSION['rental_cart'] = $cart;
            setFlash('success', 'Keranjang diperbarui.');
        }
        redirect('customer/cart.php');
    }
}

$cart = getCart();
$items = [];
$total = 0.0;
if ($cart) {
    $ids = array_values(array_filter(array_map('intval', array_keys($cart)), fn($id) => $id > 0));
    if ($ids) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id,title,author,cover_image,rental_price,stock FROM comics WHERE id IN ($marks) ORDER BY title");
        $stmt->execute($ids);
        $items = $stmt->fetchAll();
    }
}
foreach ($items as &$item) {
    $item['quantity'] = (int)($cart[$item['id']] ?? 0);
    $item['line_total'] = (float)$item['rental_price'] * $item['quantity'];
    $total += $item['line_total'];
}
unset($item);
$pageTitle = 'Keranjang Sewa';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="mb-4"><div class="eyebrow">RENTAL CART</div><h1 class="h3 fw-bold">Keranjang Sewa</h1><p class="text-secondary mb-0">Periksa pilihan komik sebelum membuat transaksi.</p></div>
<?php if (!$items): ?>
<div class="card border-0 shadow-sm rounded-4 p-5 text-center"><h2 class="h5">Keranjang masih kosong</h2><p class="text-secondary">Temukan komik yang ingin kamu baca.</p><a class="btn btn-komikin align-self-center" href="<?= e(BASE_URL) ?>catalog.php">Jelajahi Katalog</a></div>
<?php else: ?>
<div class="card border-0 shadow-sm rounded-4"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Komik</th><th>Harga sewa</th><th style="width:140px">Jumlah</th><th>Subtotal</th><th></th></tr></thead><tbody>
<?php foreach($items as $item): ?><tr><td><div class="d-flex align-items-center gap-3"><?php if($item['cover_image']): ?><img class="cover-thumb" src="<?= e(BASE_URL.$item['cover_image']) ?>" alt=""><?php else: ?><div class="cover-thumb cover-placeholder">K</div><?php endif; ?><div class="fw-semibold"><?= e($item['title']) ?><div class="small text-secondary"><?= e($item['author']) ?></div></div></div></td><td><?= formatRupiah($item['rental_price']) ?></td><td><form method="post" class="d-flex gap-1"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="comic_id" value="<?= (int)$item['id'] ?>"><input class="form-control form-control-sm" type="number" name="quantity" min="1" max="20" value="<?= (int)$item['quantity'] ?>"><button class="btn btn-sm btn-outline-secondary">OK</button></form><small class="text-secondary">Stok: <?= (int)$item['stock'] ?></small></td><td class="fw-semibold"><?= formatRupiah($item['line_total']) ?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="comic_id" value="<?= (int)$item['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><div class="p-4 d-flex flex-wrap justify-content-between align-items-center gap-3"><div><div class="text-secondary small">Total sewa (<?= (int)DEFAULT_RENTAL_DAYS ?> hari)</div><div class="h4 fw-bold mb-0"><?= formatRupiah($total) ?></div></div><form method="post" action="<?= e(BASE_URL) ?>customer/checkout.php"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><button class="btn btn-komikin btn-lg">Konfirmasi Penyewaan</button></form></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
