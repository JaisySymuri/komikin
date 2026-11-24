<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdminOrEmployee();

$pageTitle = 'Kelola Komik';
$stmt = $pdo->query("SELECT c.*, cat.category_name FROM comics c LEFT JOIN categories cat ON cat.id=c.category_id ORDER BY c.created_at DESC, c.id DESC");
$comics = $stmt->fetchAll();
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div><div class="eyebrow">INVENTORY MANAGEMENT</div><h1 class="h3 fw-bold mb-1">Kelola Komik</h1><p class="text-secondary mb-0">Atur katalog, harga sewa, cover, dan ketersediaan stok.</p></div>
  <a class="btn btn-komikin" href="<?= e(BASE_URL) ?>admin/comics/create.php">+ Tambah Komik</a>
</div>
<div class="card border-0 shadow-sm rounded-4">
 <div class="table-responsive">
  <table class="table align-middle mb-0">
   <thead class="table-light"><tr><th>Komik</th><th>Genre</th><th>Harga sewa</th><th>Stok</th><th class="text-end">Aksi</th></tr></thead>
   <tbody>
   <?php if (!$comics): ?><tr><td colspan="5" class="text-center text-secondary py-5">Belum ada data komik.</td></tr><?php endif; ?>
   <?php foreach ($comics as $comic): ?>
    <tr>
     <td><div class="d-flex align-items-center gap-3">
      <?php if ($comic['cover_image']): ?><img class="cover-thumb" src="<?= e(BASE_URL . $comic['cover_image']) ?>" alt="Cover <?= e($comic['title']) ?>"><?php else: ?><div class="cover-placeholder">K</div><?php endif; ?>
      <div><div class="fw-semibold"><?= e($comic['title']) ?></div><small class="text-secondary"><?= e($comic['author']) ?></small></div>
     </div></td>
     <td><?= e($comic['category_name'] ?? '-') ?></td>
     <td><?= formatRupiah($comic['rental_price']) ?></td>
     <td><span class="badge <?= (int)$comic['stock'] > 0 ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (int)$comic['stock'] ?></span></td>
     <td class="text-end text-nowrap">
      <a class="btn btn-sm btn-outline-primary" href="<?= e(BASE_URL) ?>admin/comics/edit.php?id=<?= (int)$comic['id'] ?>">Edit</a>
      <form class="d-inline" method="post" action="<?= e(BASE_URL) ?>admin/comics/delete.php" onsubmit="return confirm('Hapus komik ini?');">
       <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int)$comic['id'] ?>">
       <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
      </form>
     </td>
    </tr>
   <?php endforeach; ?>
   </tbody>
  </table>
 </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
