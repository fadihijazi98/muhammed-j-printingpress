<?php

declare(strict_types=1);

namespace App;

/** Material purchases and worker wages — the two kinds of money going out. */
final class ExpenseRepository
{
    public static function materialPurchases(array $filters = [], int $limit = 200): array
    {
        [$where, $bindings] = self::dateFilters($filters, 'p.purchase_date');

        if (! empty($filters['material_id'])) {
            $where[]    = 'p.material_id = ?';
            $bindings[] = (int) $filters['material_id'];
        }

        return Database::select(
            'SELECT p.*, m.name AS material_name, m.unit AS material_unit,
                    c.name AS created_by_name, e.name AS updated_by_name,
                    mer.name AS sale_merchant_name
             FROM material_purchases p
             JOIN materials m ON m.id = p.material_id
             LEFT JOIN sales s ON s.id = p.sale_id
             LEFT JOIN merchants mer ON mer.id = s.merchant_id
             LEFT JOIN users c ON c.id = p.created_by
             LEFT JOIN users e ON e.id = p.updated_by
             ' . ($where === [] ? '' : 'WHERE ' . implode(' AND ', $where)) . '
             ORDER BY p.purchase_date DESC, p.id DESC
             LIMIT ' . $limit,
            $bindings,
        );
    }

    public static function findMaterialPurchase(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM material_purchases WHERE id = ?', [$id]);
    }

    public static function workerPayments(array $filters = [], int $limit = 200): array
    {
        [$where, $bindings] = self::dateFilters($filters, 'p.work_date');

        if (! empty($filters['worker_id'])) {
            $where[]    = 'p.worker_id = ?';
            $bindings[] = (int) $filters['worker_id'];
        }

        return Database::select(
            'SELECT p.*, w.name AS worker_name,
                    c.name AS created_by_name, e.name AS updated_by_name,
                    mer.name AS sale_merchant_name
             FROM worker_payments p
             JOIN workers w ON w.id = p.worker_id
             LEFT JOIN sales s ON s.id = p.sale_id
             LEFT JOIN merchants mer ON mer.id = s.merchant_id
             LEFT JOIN users c ON c.id = p.created_by
             LEFT JOIN users e ON e.id = p.updated_by
             ' . ($where === [] ? '' : 'WHERE ' . implode(' AND ', $where)) . '
             ORDER BY p.work_date DESC, p.id DESC
             LIMIT ' . $limit,
            $bindings,
        );
    }

    public static function findWorkerPayment(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM worker_payments WHERE id = ?', [$id]);
    }

    public static function materialTotalBetween(string $from, string $to): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(total), 0) FROM material_purchases WHERE purchase_date BETWEEN ? AND ?',
            [$from, $to],
        );
    }

    public static function workerTotalBetween(string $from, string $to): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(total), 0) FROM worker_payments WHERE work_date BETWEEN ? AND ?',
            [$from, $to],
        );
    }

    public static function materialBreakdown(string $from, string $to): array
    {
        return Database::select(
            'SELECT m.name, m.unit, SUM(p.quantity) AS quantity, SUM(p.total) AS total
             FROM material_purchases p
             JOIN materials m ON m.id = p.material_id
             WHERE p.purchase_date BETWEEN ? AND ?
             GROUP BY m.id
             ORDER BY total DESC',
            [$from, $to],
        );
    }

    public static function workerBreakdown(string $from, string $to): array
    {
        return Database::select(
            'SELECT w.name, SUM(p.hours) AS hours, SUM(p.total) AS total
             FROM worker_payments p
             JOIN workers w ON w.id = p.worker_id
             WHERE p.work_date BETWEEN ? AND ?
             GROUP BY w.id
             ORDER BY total DESC',
            [$from, $to],
        );
    }

    private static function dateFilters(array $filters, string $column): array
    {
        $where    = [];
        $bindings = [];

        if (! empty($filters['from'])) {
            $where[]    = $column . ' >= ?';
            $bindings[] = $filters['from'];
        }

        if (! empty($filters['to'])) {
            $where[]    = $column . ' <= ?';
            $bindings[] = $filters['to'];
        }

        return [$where, $bindings];
    }
}
