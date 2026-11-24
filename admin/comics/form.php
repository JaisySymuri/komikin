<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdminOrEmployee();

function uploadComicCover(array $file, ?string $oldCover = null): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $oldCover;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Upload cover gagal. Coba ulangi.');
    if (!isset($file['size']) || (int)$file['size'] < 1 || (int)$file['size'] > MAX_COVER_UPLOAD_BYTES) throw new RuntimeException('Ukuran cover harus lebih dari 0 dan maksimal 2 MB.');
    $tmp = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp)) throw new RuntimeException('File upload tidak valid.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $imageInfo = @getimagesize($tmp);
    if (!isset($allowed[$mime]) || $imageInfo === false) throw new RuntimeException('Format cover harus JPG, PNG, atau WEBP.');
    if (($imageInfo[0] ?? 0) > 5000 || ($imageInfo[1] ?? 0) > 5000) throw new RuntimeException('Dimensi cover maksimal 5000 x 5000 piksel.');
    $dir = COVER_UPLOAD_DIR;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Folder upload tidak dapat dibuat.');
    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($tmp, $dir . $filename)) throw new RuntimeException('Cover tidak berhasil disimpan.');
    return 'uploads/comic-covers/' . $filename;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$comic = ['title'=>'','author'=>'','category_id'=>'','description'=>'','rental_price'=>'5000','stock'=>'1','cover_image'=>null];
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM comics WHERE id = ?');
    $stmt->execute([$id]);
    $comic = $stmt->fetch();
    if (!$comic) { http_response_code(404); exit('Komik tidak ditemukan.'); }
}
$categories = $pdo->query('SELECT id, category_name FROM categories ORDER BY category_name')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) $errors[] = 'Sesi form tidak valid. Muat ulang halaman.';
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = filter_var($_POST['rental_price'] ?? null, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
    if ($title === '' || mb_strlen($title) > 180) $errors[] = 'Judul wajib diisi (maksimal 180 karakter).';
    if ($author === '' || mb_strlen($author) > 150) $errors[] = 'Penulis wajib diisi (maksimal 150 karakter).';
    if ($categoryId < 1) $errors[] = 'Pilih genre komik.';
    if ($price === false || $price < 0) $errors[] = 'Harga sewa harus berupa angka nol atau lebih.';
    if ($stock === false || $stock < 0) $errors[] = 'Stok harus berupa bilangan bulat nol atau lebih.';
    if (!$errors) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = ?');
        $check->execute([$categoryId]);
        if (!(int)$check->fetchColumn()) $errors[] = 'Genre yang dipilih tidak tersedia.';
    }
    if (!$errors) {
        $newCoverPath = null;
        try {
            $oldCoverPath = $comic['cover_image'] ?? null;
            $cover = uploadComicCover($_FILES['cover_image'] ?? [], $oldCoverPath);
            if ($cover !== $oldCoverPath) $newCoverPath = $cover;
            if ($isEdit) {
                $stmt = $pdo->prepare('UPDATE comics SET category_id=?, title=?, author=?, description=?, cover_image=?, rental_price=?, stock=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([$categoryId,$title,$author,$description,$cover,$price,$stock,$id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO comics (category_id,title,author,description,cover_image,rental_price,stock,created_at,updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())');
                $stmt->execute([$categoryId,$title,$author,$description,$cover,$price,$stock]);
            }
            if ($newCoverPath && $oldCoverPath) {
                $safeOldCover = 'uploads/comic-covers/' . basename($oldCoverPath);
                if ($oldCoverPath === $safeOldCover) {
                    $oldPath = dirname(__DIR__, 2) . '/' . $safeOldCover;
                    if (is_file($oldPath)) @unlink($oldPath);
                }
            }
            setFlash('success', $isEdit ? 'Data komik berhasil diperbarui.' : 'Komik berhasil ditambahkan.');
            redirect('admin/comics/index.php');
        } catch (Throwable $e) {
            if (!empty($newCoverPath)) {
                $newPath = dirname(__DIR__, 2) . '/' . $newCoverPath;
                if (is_file($newPath)) @unlink($newPath);
            }
            $errors[] = APP_DEBUG ? $e->getMessage() : 'Data komik gagal disimpan. Periksa kembali input dan file cover.';
        }
    }
    $comic = array_merge($comic, ['title'=>$title,'author'=>$author,'category_id'=>$categoryId,'description'=>$description,'rental_price'=>$_POST['rental_price'] ?? '','stock'=>$_POST['stock'] ?? '']);
}
$pageTitle = $isEdit ? 'Edit Komik' : 'Tambah Komik';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="mb-4"><a href="<?= e(BASE_URL) ?>admin/comics/index.php" class="text-decoration-none">&larr; Kembali ke daftar komik</a><h1 class="h3 fw-bold mt-3"><?= $isEdit ? 'Edit Komik' : 'Tambah Komik' ?></h1><p class="text-secondary mb-0">Lengkapi informasi komik di bawah ini.</p></div>
<div class="card border-0 shadow-sm rounded-4 p-4">
 <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
 <form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
  <div class="row g-3">
   <div class="col-md-8"><label class="form-label fw-semibold">Judul komik *</label><input class="form-control" name="title" maxlength="180" required value="<?= e($comic['title']) ?>"></div>
   <div class="col-md-4"><label class="form-label fw-semibold">Penulis *</label><input class="form-control" name="author" maxlength="150" required value="<?= e($comic['author']) ?>"></div>
   <div class="col-md-5"><label class="form-label fw-semibold">Genre *</label><select class="form-select" name="category_id" required><option value="">Pilih genre</option><?php foreach ($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)$comic['category_id']===(int)$cat['id']?'selected':'' ?>><?= e($cat['category_name']) ?></option><?php endforeach; ?></select><?php if (!$categories): ?><small class="text-danger">Buat kategori terlebih dahulu.</small><?php endif; ?></div>
   <div class="col-md-4"><label class="form-label fw-semibold">Harga sewa (Rp) *</label><input class="form-control" type="number" name="rental_price" min="0" step="500" required value="<?= e((string)$comic['rental_price']) ?>"></div>
   <div class="col-md-3"><label class="form-label fw-semibold">Stok *</label><input class="form-control" type="number" name="stock" min="0" step="1" required value="<?= e((string)$comic['stock']) ?>"></div>
   <div class="col-12"><label class="form-label fw-semibold">Deskripsi</label><textarea class="form-control" name="description" rows="4"><?= e($comic['description']) ?></textarea></div>
   <div class="col-md-7"><label class="form-label fw-semibold">Cover komik</label><input class="form-control" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG, WEBP. Maksimal 2 MB. Kosongkan jika tidak ingin mengganti cover.</div></div>
   <?php if (!empty($comic['cover_image'])): ?><div class="col-md-5"><label class="form-label fw-semibold d-block">Cover saat ini</label><img class="cover-preview" src="<?= e(BASE_URL . $comic['cover_image']) ?>" alt="Cover saat ini"></div><?php endif; ?>
  </div>
  <div class="d-flex gap-2 mt-4"><button class="btn btn-komikin" type="submit" <?= !$categories?'disabled':'' ?>>Simpan Komik</button><a class="btn btn-light" href="<?= e(BASE_URL) ?>admin/comics/index.php">Batal</a></div>
 </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
