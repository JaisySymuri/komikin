<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdminOrEmployee();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400); exit('Permintaan tidak valid.');
}
$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT cover_image FROM comics WHERE id=?');
$stmt->execute([$id]);
$comic = $stmt->fetch();
if (!$comic) { setFlash('warning','Komik tidak ditemukan.'); redirect('admin/comics/index.php'); }
$used = $pdo->prepare('SELECT COUNT(*) FROM rental_details WHERE comic_id=?');
$used->execute([$id]);
if ((int)$used->fetchColumn() > 0) {
    setFlash('danger','Komik tidak dapat dihapus karena sudah memiliki riwayat transaksi. Ubah stok menjadi 0 agar tidak disewakan lagi.');
    redirect('admin/comics/index.php');
}
$del = $pdo->prepare('DELETE FROM comics WHERE id=?');
$del->execute([$id]);
if ($comic['cover_image']) {
    $path = dirname(__DIR__, 2) . '/' . $comic['cover_image'];
    if (is_file($path)) @unlink($path);
}
setFlash('success','Komik berhasil dihapus.');
redirect('admin/comics/index.php');
