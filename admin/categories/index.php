<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdminOrEmployee();
$pageTitle = 'Kelola Genre';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) $errors[]='Sesi form tidak valid.';
    else {
        $name = trim($_POST['category_name'] ?? '');
        if ($name === '' || mb_strlen($name)>80) $errors[]='Nama genre wajib diisi (maksimal 80 karakter).';
        else {
            try {
                $stmt=$pdo->prepare('INSERT INTO categories (category_name,created_at) VALUES (?,NOW())');
                $stmt->execute([$name]);
                setFlash('success','Genre berhasil ditambahkan.');
                redirect('admin/categories/index.php');
            } catch (PDOException $e) { $errors[]='Genre sudah ada atau tidak dapat disimpan.'; }
        }
    }
}
$categories=$pdo->query('SELECT c.id,c.category_name,c.created_at,COUNT(co.id) AS comic_count FROM categories c LEFT JOIN comics co ON co.category_id=c.id GROUP BY c.id,c.category_name,c.created_at ORDER BY c.category_name')->fetchAll();
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-4"><div class="eyebrow">CATALOG SETTINGS</div><h1 class="h3 fw-bold">Kelola Genre</h1><p class="text-secondary">Genre digunakan untuk mengelompokkan katalog komik.</p></div>
<div class="row g-4">
 <div class="col-lg-4"><div class="card border-0 shadow-sm rounded-4 p-4"><h2 class="h5 fw-bold">Tambah genre</h2><?php foreach($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><label class="form-label">Nama genre</label><input class="form-control mb-3" name="category_name" maxlength="80" required placeholder="Contoh: Superhero"><button class="btn btn-komikin" type="submit">Tambah Genre</button></form></div></div>
 <div class="col-lg-8"><div class="card border-0 shadow-sm rounded-4"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Genre</th><th>Jumlah komik</th><th class="text-end">Aksi</th></tr></thead><tbody><?php foreach($categories as $cat): ?><tr><td class="fw-semibold"><?= e($cat['category_name']) ?></td><td><?= (int)$cat['comic_count'] ?></td><td class="text-end"><?php if ((int)$cat['comic_count']===0): ?><form method="post" action="<?= e(BASE_URL) ?>admin/categories/delete.php" onsubmit="return confirm('Hapus genre ini?')"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>"><button class="btn btn-sm btn-outline-danger">Hapus</button></form><?php else: ?><span class="text-secondary small">Sedang digunakan</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
