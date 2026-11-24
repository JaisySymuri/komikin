<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdminOrEmployee();
if ($_SERVER['REQUEST_METHOD']!=='POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) { http_response_code(400); exit('Permintaan tidak valid.'); }
$id=(int)($_POST['id'] ?? 0);
$stmt=$pdo->prepare('SELECT COUNT(*) FROM comics WHERE category_id=?'); $stmt->execute([$id]);
if ((int)$stmt->fetchColumn()>0) setFlash('danger','Genre masih digunakan oleh komik dan tidak dapat dihapus.');
else { $del=$pdo->prepare('DELETE FROM categories WHERE id=?'); $del->execute([$id]); setFlash('success','Genre berhasil dihapus.'); }
redirect('admin/categories/index.php');
