<?php

declare(strict_types=1);

namespace App;

/**
 * The cost lines attached to one sale. They live in the same tables as the
 * shop's general expenses, tagged with sale_id, so a cost is counted once in
 * the period reports whether or not it belongs to a particular sale.
 */
final class SaleCostRepository
{
    public static function materialsFor(int $saleId): array
    {
        return Database::select(
            'SELECT p.*, m.name AS material_name, m.unit AS material_unit
             FROM material_purchases p
             JOIN materials m ON m.id = p.material_id
             WHERE p.sale_id = ?
             ORDER BY p.id',
            [$saleId],
        );
    }

    public static function workersFor(int $saleId): array
    {
        return Database::select(
            'SELECT p.*, w.name AS worker_name
             FROM worker_payments p
             JOIN workers w ON w.id = p.worker_id
             WHERE p.sale_id = ?
             ORDER BY p.id',
            [$saleId],
        );
    }

    public static function materialTotalFor(int $saleId): int
    {
        return (int) Database::scalar('SELECT COALESCE(SUM(total), 0) FROM material_purchases WHERE sale_id = ?', [$saleId]);
    }

    public static function workerTotalFor(int $saleId): int
    {
        return (int) Database::scalar('SELECT COALESCE(SUM(total), 0) FROM worker_payments WHERE sale_id = ?', [$saleId]);
    }

    /** Sale price, what it cost to produce, and what is left. */
    public static function breakdown(int $saleId, int $saleTotal): array
    {
        $materials = self::materialTotalFor($saleId);
        $workers   = self::workerTotalFor($saleId);

        return [
            'sale_total'     => $saleTotal,
            'material_costs' => $materials,
            'worker_costs'   => $workers,
            'total_costs'    => $materials + $workers,
            'net_profit'     => $saleTotal - $materials - $workers,
        ];
    }

    /** Replaces every cost line of a sale. Callers must already be in a transaction. */
    public static function replaceFor(int $saleId, array $materialLines, array $workerLines, string $date): void
    {
        Database::run('DELETE FROM material_purchases WHERE sale_id = ?', [$saleId]);
        Database::run('DELETE FROM worker_payments WHERE sale_id = ?', [$saleId]);

        foreach ($materialLines as $line) {
            Database::insert(
                'material_purchases',
                [
                    'material_id'   => $line['material_id'],
                    'quantity'      => $line['quantity'],
                    'unit_cost'     => $line['unit_cost'],
                    'total'         => $line['total'],
                    'purchase_date' => $date,
                    'note'          => $line['note'] ?: null,
                    'sale_id'       => $saleId,
                    'created_by'    => Auth::id(),
                ],
            );
        }

        foreach ($workerLines as $line) {
            Database::insert(
                'worker_payments',
                [
                    'worker_id'   => $line['worker_id'],
                    'hours'       => $line['hours'],
                    'hourly_rate' => $line['hourly_rate'],
                    'total'       => $line['total'],
                    'work_date'   => $date,
                    'note'        => $line['note'] ?: null,
                    'sale_id'     => $saleId,
                    'created_by'  => Auth::id(),
                ],
            );
        }
    }
}
