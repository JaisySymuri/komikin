<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$pageTitle='Katalog Komik';
$q=trim($_GET['q'] ?? '');
$categoryId=(int)($_GET['category'] ?? 0);
$categories=$pdo->query('SELECT id,category_name FROM categories ORDER BY category_name')->fetchAll();
$sql='SELECT c.id,c.title,c.author,c.cover_image,c.rental_price,c.stock,cat.category_name FROM comics c LEFT JOIN categories cat ON cat.id=c.category_id WHERE 1=1';
$params=[];
if ($q!=='') { $sql.=' AND (c.title LIKE ? OR c.author LIKE ?)'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
if ($categoryId>0) { $sql.=' AND c.category_id=?'; $params[]=$categoryId; }
$sql.=' ORDER BY c.created_at DESC,c.id DESC';
$stmt=$pdo->prepare($sql); $stmt->execute($params); $comics=$stmt->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><div class="eyebrow">TEMUKAN CERITAMU</div><h1 class="h2 fw-bold mb-1">Katalog Komik</h1><p class="text-secondary mb-0">Pilih komik favoritmu dan mulai petualangan membaca.</p></div><span class="text-secondary"><?= count($comics) ?> komik ditemukan</span></div>
<form class="card border-0 shadow-sm rounded-4 p-3 mb-4" method="get"><div class="row g-2"><div class="col-md-7"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau penulis..."></div><div class="col-md-3"><select class="form-select" name="category"><option value="0">Semua genre</option><?php foreach($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $categoryId===(int)$cat['id']?'selected':'' ?>><?= e($cat['category_name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2 d-grid"><button class="btn btn-komikin">Cari Komik</button></div></div></form>
<div class="row g-4">
<?php if (!$comics): ?><div class="col-12"><div class="text-center bg-white rounded-4 p-5"><h2 class="h5">Komik tidak ditemukan</h2><p class="text-secondary mb-0">Coba kata kunci atau genre lainnya.</p></div></div><?php endif; ?>
<?php foreach($comics as $comic): ?><div class="col-6 col-md-4 col-lg-3"><div class="card comic-card h-100 border-0 shadow-sm"><?php if($comic['cover_image']): ?><img class="comic-cover" src="<?= e(BASE_URL.$comic['cover_image']) ?>" alt="Cover <?= e($comic['title']) ?>"><?php else: ?><div class="comic-cover cover-placeholder">KOMIKIN</div><?php endif; ?><div class="card-body d-flex flex-column"><span class="small text-primary fw-semibold"><?= e($comic['category_name'] ?? 'Tanpa genre') ?></span><h2 class="h6 fw-bold mt-1"><?= e($comic['title']) ?></h2><p class="small text-secondary mb-2"><?= e($comic['author']) ?></p><div class="mt-auto"><div class="fw-bold"><?= formatRupiah($comic['rental_price']) ?><small class="text-secondary fw-normal"> / 7 hari</small></div><div class="small <?= (int)$comic['stock']>0?'text-success':'text-danger' ?> mb-3"><?= (int)$comic['stock']>0?'Stok: '.(int)$comic['stock']:'Stok habis' ?></div><a class="btn btn-sm btn-outline-dark w-100" href="<?= e(BASE_URL) ?>comic.php?id=<?= (int)$comic['id'] ?>">Lihat Detail</a></div></div></div></div><?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
