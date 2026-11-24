<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/rental_helpers.php';
$pageTitle = $pageTitle ?? APP_NAME;
$user = currentUser();
$flash = getFlash();
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(BASE_URL) ?>assets/css/style.css" rel="stylesheet"><link href="<?= e(BASE_URL) ?>assets/css/phase5.css" rel="stylesheet"></head><body>
<nav class="navbar navbar-expand-lg navbar-dark komikin-navbar"><div class="container"><a class="navbar-brand fw-bold" href="<?= e(BASE_URL) ?>">KOMIKIN<span class="brand-dot">.</span></a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="mainNav"><div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
<a class="nav-link" href="<?= e(BASE_URL) ?>">Beranda</a><a class="nav-link" href="<?= e(BASE_URL) ?>catalog.php">Katalog</a>
<?php if($user): ?><?php if(in_array($user['role'], ['admin','employee'], true)): ?><a class="nav-link" href="<?= e(BASE_URL) ?>admin/index.php">Dashboard</a><a class="nav-link" href="<?= e(BASE_URL) ?>admin/comics/index.php">Komik</a><a class="nav-link" href="<?= e(BASE_URL) ?>admin/categories/index.php">Genre</a><a class="nav-link" href="<?= e(BASE_URL) ?>admin/customers/index.php">Pelanggan</a><a class="nav-link" href="<?= e(BASE_URL) ?>admin/rentals/index.php">Penyewaan</a><?php if($user['role']==='admin'): ?><a class="nav-link" href="<?= e(BASE_URL) ?>admin/reports/index.php">Laporan</a><a class="nav-link" href="<?= e(BASE_URL) ?>admin/employees/index.php">Employee</a><?php endif; ?><?php else: ?><a class="nav-link" href="<?= e(BASE_URL) ?>customer/index.php">Akun Saya</a><a class="nav-link" href="<?= e(BASE_URL) ?>customer/rentals.php">Transaksi</a><a class="nav-link" href="<?= e(BASE_URL) ?>customer/cart.php">Keranjang (<?= cartCount() ?>)</a><?php endif; ?><span class="navbar-text small text-white-50"><?= e($user['name']) ?> · <?= e(ucfirst($user['role'])) ?></span><form method="post" action="<?= e(BASE_URL) ?>logout.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><button class="btn btn-sm btn-outline-light ms-lg-2" type="submit">Logout</button></form><?php else: ?><a class="nav-link" href="<?= e(BASE_URL) ?>login.php">Login</a><a class="btn btn-sm btn-komikin ms-lg-2" href="<?= e(BASE_URL) ?>register.php">Daftar</a><?php endif; ?>
</div></div></div></nav><main class="container py-4">
<?php if($flash): ?><div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert"><?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div><?php endif; ?>
