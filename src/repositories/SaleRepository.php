<?php

declare(strict_types=1);

namespace App;

final class SaleRepository
{
    public static function recent(array $filters = [], int $limit = 200): array
    {
        [$where, $bindings] = self::filters($filters);

        $rows = Database::select(
            'SELECT
                s.*,
                m.name AS merchant_name,
                j.name AS job_type_name,
                j.unit AS job_unit,
                c.name AS created_by_name,
                e.name AS updated_by_name,
                COALESCE((SELECT SUM(total) FROM material_purchases WHERE sale_id = s.id), 0) AS material_costs,
                COALESCE((SELECT SUM(total) FROM worker_payments    WHERE sale_id = s.id), 0) AS worker_costs
             FROM sales s
             JOIN merchants m ON m.id = s.merchant_id
             LEFT JOIN job_types j ON j.id = s.job_type_id
             LEFT JOIN users c ON c.id = s.created_by
             LEFT JOIN users e ON e.id = s.updated_by
             ' . $where . '
             ORDER BY s.sale_date DESC, s.id DESC
             LIMIT ' . $limit,
            $bindings,
        );

        return array_map(self::withProfit(...), $rows);
    }

    public static function find(int $id): ?array
    {
        $row = Database::selectOne(
            'SELECT
                s.*,
                m.name AS merchant_name,
                j.name AS job_type_name,
                j.unit AS job_unit,
                c.name AS created_by_name,
                e.name AS updated_by_name,
                COALESCE((SELECT SUM(total) FROM material_purchases WHERE sale_id = s.id), 0) AS material_costs,
                COALESCE((SELECT SUM(total) FROM worker_payments    WHERE sale_id = s.id), 0) AS worker_costs
             FROM sales s
             JOIN merchants m ON m.id = s.merchant_id
             LEFT JOIN job_types j ON j.id = s.job_type_id
             LEFT JOIN users c ON c.id = s.created_by
             LEFT JOIN users e ON e.id = s.updated_by
             WHERE s.id = ?',
            [$id],
        );

        return $row === null ? null : self::withProfit($row);
    }

    /**
     * The stored total is what the merchant is actually charged; `calculated`
     * is plain count × unit price, kept so the screen can show the difference
     * whenever a price was agreed down.
     */
    private static function withProfit(array $row): array
    {
        $row['total']          = (int) $row['total'];
        $row['unit_price']     = (int) $row['unit_price'];
        $row['quantity']       = (float) $row['quantity'];
        $row['material_costs'] = (int) $row['material_costs'];
        $row['worker_costs']   = (int) $row['worker_costs'];

        $row['calculated_total'] = Money::multiply($row['quantity'], $row['unit_price']);
        $row['is_adjusted']      = $row['calculated_total'] !== $row['total'];
        $row['total_costs']      = $row['material_costs'] + $row['worker_costs'];
        $row['net_profit']       = $row['total'] - $row['total_costs'];

        return $row;
    }

    public static function totalBetween(string $from, string $to): int
    {
        return (int) Database::scalar(
            'SELECT COALESCE(SUM(total), 0) FROM sales WHERE sale_date BETWEEN ? AND ?',
            [$from, $to],
        );
    }

    private static function filters(array $filters): array
    {
        $where    = [];
        $bindings = [];

        if (! empty($filters['merchant_id'])) {
            $where[]    = 's.merchant_id = ?';
            $bindings[] = (int) $filters['merchant_id'];
        }

        if (! empty($filters['from'])) {
            $where[]    = 's.sale_date >= ?';
            $bindings[] = $filters['from'];
        }

        if (! empty($filters['to'])) {
            $where[]    = 's.sale_date <= ?';
            $bindings[] = $filters['to'];
        }

        return [$where === [] ? '' : 'WHERE ' . implode(' AND ', $where), $bindings];
    }
}
