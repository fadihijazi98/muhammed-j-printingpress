<?php

declare(strict_types=1);

namespace App;

final class DiscountRepository
{
    public static function recent(int $limit = 200): array
    {
        return Database::select(
            'SELECT d.*, m.name AS merchant_name, c.name AS created_by_name
             FROM discounts d
             JOIN merchants m ON m.id = d.merchant_id
             LEFT JOIN users c ON c.id = d.created_by
             ORDER BY d.discount_date DESC, d.id DESC
             LIMIT ' . $limit,
        );
    }

    public static function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM discounts WHERE id = ?', [$id]);
    }

    public static function totalBetween(string $from, string $to): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(amount), 0) FROM discounts WHERE discount_date BETWEEN ? AND ?',
            [$from, $to],
        );
    }
}
