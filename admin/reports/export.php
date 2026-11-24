<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');
$validDate = static function ($value): bool {
    if (!is_string($value)) return false;
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
};
if (!$validDate($start) || !$validDate($end) || $start > $end) {
    http_response_code(400); exit('Rentang tanggal tidak valid.');
}

$stmt = $pdo->prepare("SELECT r.id,r.rental_date,r.due_date,r.status,u.name AS customer_name,u.email,
    c.title AS comic_title,rd.quantity,rd.price,(rd.quantity*rd.price) AS line_total,
    COALESCE(ret.returned_qty,0) AS returned_qty,COALESCE(ret.late_fee,0) AS late_fee
    FROM rentals r JOIN users u ON u.id=r.user_id
    JOIN rental_details rd ON rd.rental_id=r.id
    JOIN comics c ON c.id=rd.comic_id
    LEFT JOIN (SELECT rental_detail_id,SUM(quantity) AS returned_qty,SUM(late_fee) AS late_fee
               FROM rental_item_returns GROUP BY rental_detail_id) ret ON ret.rental_detail_id=rd.id
    WHERE DATE(r.rental_date) BETWEEN ? AND ?
    ORDER BY r.rental_date DESC,r.id DESC,c.title");
$stmt->execute([$start,$end]);

$filename = 'komikin-report-' . $start . '-to-' . $end . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
fputcsv($out, ['Rental ID','Tanggal Sewa','Jatuh Tempo','Status','Nama Pelanggan','Email','Judul Komik','Qty','Harga Sewa','Subtotal','Qty Kembali','Denda'], ',', '"', '');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Prefix spreadsheet-formula-leading values to avoid CSV formula injection.
    foreach (['customer_name','email','comic_title','status'] as $field) {
        if (isset($row[$field]) && preg_match('/^[\s]*[=+\-@]/u', (string)$row[$field])) {
            $row[$field] = "'" . $row[$field];
        }
    }
    fputcsv($out, [
        (int)$row['id'],$row['rental_date'],$row['due_date'],$row['status'],
        $row['customer_name'],$row['email'],$row['comic_title'],
        (int)$row['quantity'],(float)$row['price'],(float)$row['line_total'],
        (int)$row['returned_qty'],(float)$row['late_fee']
    ], ',', '"', '');
}
fclose($out);
exit;
