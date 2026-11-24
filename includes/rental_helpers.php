<?php
declare(strict_types=1);

function getCart(): array
{
    return isset($_SESSION['rental_cart']) && is_array($_SESSION['rental_cart'])
        ? $_SESSION['rental_cart'] : [];
}

function cartCount(): int
{
    return array_sum(array_map('intval', getCart()));
}

function getRentalItemReturnSummary(PDO $pdo, int $detailId): array
{
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) AS returned_qty, COALESCE(SUM(late_fee),0) AS late_fee FROM rental_item_returns WHERE rental_detail_id=?');
    $stmt->execute([$detailId]);
    $row = $stmt->fetch() ?: [];
    return ['returned_qty'=>(int)($row['returned_qty'] ?? 0), 'late_fee'=>(float)($row['late_fee'] ?? 0)];
}

function calculateLateFee(string $dueDate, string $returnedAt, int $quantity): float
{
    $due = new DateTimeImmutable($dueDate);
    $returned = new DateTimeImmutable(substr($returnedAt, 0, 10));
    $days = max(0, (int)$due->diff($returned)->format('%r%a'));
    return $days * (float)LATE_FEE_PER_DAY * $quantity;
}
