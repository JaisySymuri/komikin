<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rental_helpers.php';
requireCustomer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400); exit('Permintaan tidak valid.');
}
$cart = getCart();
if (!$cart) { setFlash('warning','Keranjang sewa masih kosong.'); redirect('customer/cart.php'); }

try {
    $pdo->beginTransaction();
    $validated = [];
    $total = 0.0;
    foreach ($cart as $comicId => $quantity) {
        $comicId = (int)$comicId;
        $quantity = (int)$quantity;
        if ($comicId < 1 || $quantity < 1 || $quantity > 20) throw new RuntimeException('Isi keranjang tidak valid.');
        $stmt = $pdo->prepare('SELECT id,title,rental_price,stock FROM comics WHERE id=? FOR UPDATE');
        $stmt->execute([$comicId]);
        $comic = $stmt->fetch();
        if (!$comic) throw new RuntimeException('Salah satu komik di keranjang sudah tidak tersedia.');
        if ((int)$comic['stock'] < $quantity) throw new RuntimeException('Stok komik "' . $comic['title'] . '" tidak mencukupi. Perbarui jumlah di keranjang.');
        $validated[] = ['id'=>$comicId,'quantity'=>$quantity,'price'=>(float)$comic['rental_price']];
        $total += (float)$comic['rental_price'] * $quantity;
    }

    $stmt = $pdo->prepare("INSERT INTO rentals (user_id,rental_date,due_date,status,total_price,created_at,updated_at) VALUES (?,NOW(),DATE_ADD(CURDATE(), INTERVAL ? DAY),'active',?,NOW(),NOW())");
    $stmt->execute([(int)currentUser()['id'], (int)DEFAULT_RENTAL_DAYS, $total]);
    $rentalId = (int)$pdo->lastInsertId();

    $detail = $pdo->prepare('INSERT INTO rental_details (rental_id,comic_id,quantity,price,returned_at,late_fee,created_at) VALUES (?,?,?,?,NULL,0,NOW())');
    $stockUpdate = $pdo->prepare('UPDATE comics SET stock=stock-?, updated_at=NOW() WHERE id=? AND stock>=?');
    foreach ($validated as $line) {
        $detail->execute([$rentalId,$line['id'],$line['quantity'],$line['price']]);
        $stockUpdate->execute([$line['quantity'],$line['id'],$line['quantity']]);
        if ($stockUpdate->rowCount() !== 1) throw new RuntimeException('Stok berubah saat transaksi. Silakan coba kembali.');
    }
    $pdo->commit();
    unset($_SESSION['rental_cart']);
    setFlash('success', 'Penyewaan berhasil dibuat. Kode transaksi: #' . $rentalId);
    redirect('customer/rental-detail.php?id=' . $rentalId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    setFlash('danger', APP_DEBUG ? $e->getMessage() : 'Penyewaan gagal diproses. Silakan periksa keranjang dan coba lagi.');
    redirect('customer/cart.php');
}
