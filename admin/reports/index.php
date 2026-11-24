<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$today = date('Y-m-d');
$defaultStart = date('Y-m-01');
$start = $_GET['start'] ?? $defaultStart;
$end = $_GET['end'] ?? $today;
$validDate = static function ($value): bool {
    if (!is_string($value)) return false;
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
};
if (!$validDate($start) || !$validDate($end) || $start > $end) {
    $start = $defaultStart;
    $end = $today;
    setFlash('warning', 'Rentang tanggal tidak valid. Menampilkan periode bulan ini.');
}

$summary = $pdo->prepare("SELECT COUNT(*) AS rental_count,
    COALESCE(SUM(total_price),0) AS rental_revenue
    FROM rentals WHERE DATE(rental_date) BETWEEN ? AND ?");
$summary->execute([$start, $end]);
$totals = $summary->fetch() ?: ['rental_count'=>0,'rental_revenue'=>0];

$late = $pdo->prepare("SELECT COALESCE(SUM(rr.late_fee),0) FROM rental_item_returns rr
    WHERE DATE(rr.returned_at) BETWEEN ? AND ?");
$late->execute([$start, $end]);
$lateFees = (float)$late->fetchColumn();

$qty = $pdo->prepare("SELECT COALESCE(SUM(rd.quantity),0) FROM rental_details rd
    JOIN rentals r ON r.id=rd.rental_id WHERE DATE(r.rental_date) BETWEEN ? AND ?");
$qty->execute([$start, $end]);
$rentedCopies = (int)$qty->fetchColumn();

$popular = $pdo->prepare("SELECT c.id,c.title,c.author,COALESCE(SUM(rd.quantity),0) AS rented_qty
    FROM rental_details rd JOIN rentals r ON r.id=rd.rental_id
    JOIN comics c ON c.id=rd.comic_id
    WHERE DATE(r.rental_date) BETWEEN ? AND ?
    GROUP BY c.id,c.title,c.author ORDER BY rented_qty DESC,c.title LIMIT 10");
$popular->execute([$start,$end]);
$popularComics = $popular->fetchAll();

$daily = $pdo->prepare("SELECT DATE(rental_date) AS rental_day,COUNT(*) AS transaction_count,
    COALESCE(SUM(total_price),0) AS revenue FROM rentals
    WHERE DATE(rental_date) BETWEEN ? AND ? GROUP BY DATE(rental_date) ORDER BY rental_day DESC LIMIT 31");
$daily->execute([$start,$end]);
$dailyRows = $daily->fetchAll();

$stockRows = $pdo->query("SELECT c.id,c.title,c.stock,cat.category_name
    FROM comics c LEFT JOIN categories cat ON cat.id=c.category_id
    ORDER BY c.stock ASC,c.title LIMIT 10")->fetchAll();

$pageTitle = 'Laporan';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
 <div><div class="eyebrow">BUSINESS INTELLIGENCE</div><h1 class="h3 fw-bold mb-1">Laporan KOMIKIN</h1><p class="text-secondary mb-0">Ringkasan penyewaan, pendapatan sewa, denda, dan inventaris.</p></div>
 <a class="btn btn-outline-success" href="<?= e(BASE_URL) ?>admin/reports/export.php?start=<?= e($start) ?>&end=<?= e($end) ?>">Export CSV</a>
</div>
<form method="get" class="card border-0 shadow-sm rounded-4 p-3 mb-4"><div class="row g-2 align-items-end">
 <div class="col-md-4"><label class="form-label small fw-semibold">Dari tanggal</label><input type="date" class="form-control" name="start" value="<?= e($start) ?>" required></div>
 <div class="col-md-4"><label class="form-label small fw-semibold">Sampai tanggal</label><input type="date" class="form-control" name="end" value="<?= e($end) ?>" required></div>
 <div class="col-md-4 d-grid"><button class="btn btn-komikin">Tampilkan Laporan</button></div>
</div></form>
<div class="row g-3 mb-4">
 <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="text-secondary small">Jumlah Transaksi</div><div class="display-6 fw-bold"><?= (int)$totals['rental_count'] ?></div></div></div>
 <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="text-secondary small">Nilai Penyewaan</div><div class="h3 fw-bold mt-2"><?= formatRupiah($totals['rental_revenue']) ?></div></div></div>
 <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="text-secondary small">Denda Tercatat</div><div class="h3 fw-bold mt-2"><?= formatRupiah($lateFees) ?></div></div></div>
 <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="text-secondary small">Eksemplar Disewa</div><div class="display-6 fw-bold"><?= $rentedCopies ?></div></div></div>
</div>
<div class="row g-4">
 <div class="col-lg-7"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="p-3"><h2 class="h5 fw-bold mb-0">Komik Terpopuler</h2><small class="text-secondary">Berdasarkan jumlah eksemplar yang disewa pada periode ini.</small></div><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Komik</th><th>Penulis</th><th>Jumlah disewa</th></tr></thead><tbody><?php if(!$popularComics): ?><tr><td colspan="3" class="text-center text-secondary py-4">Belum ada penyewaan pada periode ini.</td></tr><?php endif; ?><?php foreach($popularComics as $p): ?><tr><td class="fw-semibold"><?= e($p['title']) ?></td><td><?= e($p['author']) ?></td><td><?= (int)$p['rented_qty'] ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
 <div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="p-3"><h2 class="h5 fw-bold mb-0">Perlu Perhatian: Stok</h2><small class="text-secondary">10 komik dengan stok terendah.</small></div><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Komik</th><th>Genre</th><th>Stok</th></tr></thead><tbody><?php foreach($stockRows as $s): ?><tr><td><?= e($s['title']) ?></td><td><?= e($s['category_name'] ?? '-') ?></td><td><span class="badge <?= (int)$s['stock']===0?'text-bg-danger':((int)$s['stock']<=2?'text-bg-warning':'text-bg-success') ?>"><?= (int)$s['stock'] ?></span></td></tr><?php endforeach; ?></tbody></table></div></div></div>
</div>
<div class="card border-0 shadow-sm rounded-4 mt-4"><div class="p-3"><h2 class="h5 fw-bold mb-0">Ringkasan Harian</h2></div><div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Tanggal</th><th>Transaksi</th><th>Nilai sewa</th></tr></thead><tbody><?php if(!$dailyRows): ?><tr><td colspan="3" class="text-center text-secondary py-4">Tidak ada transaksi.</td></tr><?php endif; ?><?php foreach($dailyRows as $d): ?><tr><td><?= e($d['rental_day']) ?></td><td><?= (int)$d['transaction_count'] ?></td><td><?= formatRupiah($d['revenue']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<p class="small text-secondary mt-3">Catatan: nilai penyewaan dihitung dari transaksi yang dibuat pada rentang tanggal. Denda dihitung dari pengembalian yang dicatat pada rentang tanggal. Angka ini merupakan catatan transaksi, bukan laporan akuntansi pembayaran.</p>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
