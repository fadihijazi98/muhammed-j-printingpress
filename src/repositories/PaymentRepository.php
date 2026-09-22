<?php

declare(strict_types=1);

namespace App;

final class PaymentRepository
{
    public static function recent(array $filters = [], int $limit = 200): array
    {
        $where    = [];
        $bindings = [];

        if (! empty($filters['merchant_id'])) {
            $where[]    = 'p.merchant_id = ?';
            $bindings[] = (int) $filters['merchant_id'];
        }

        if (! empty($filters['from'])) {
            $where[]    = 'p.payment_date >= ?';
            $bindings[] = $filters['from'];
        }

        if (! empty($filters['to'])) {
            $where[]    = 'p.payment_date <= ?';
            $bindings[] = $filters['to'];
        }

        return Database::select(
            'SELECT p.*, m.name AS merchant_name, c.name AS created_by_name, e.name AS updated_by_name
             FROM payments p
             JOIN merchants m ON m.id = p.merchant_id
             LEFT JOIN users c ON c.id = p.created_by
             LEFT JOIN users e ON e.id = p.updated_by
             ' . ($where === [] ? '' : 'WHERE ' . implode(' AND ', $where)) . '
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT ' . $limit,
            $bindings,
        );
    }

    public static function find(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM payments WHERE id = ?', [$id]);
    }

    public static function totalBetween(string $from, string $to): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date BETWEEN ? AND ?',
            [$from, $to],
        );
    }
}
