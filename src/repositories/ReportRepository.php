<?php

declare(strict_types=1);

namespace App;

final class ReportRepository
{
    /**
     * Profit is measured on work done (sales), not on cash collected, so an
     * unpaid job still counts as earned. "المقبوضات" shows the cash side.
     */
    public static function summary(string $from, string $to): array
    {
        $sales     = SaleRepository::totalBetween($from, $to);
        $received  = PaymentRepository::totalBetween($from, $to);
        $discounts = DiscountRepository::totalBetween($from, $to);
        $materials = ExpenseRepository::materialTotalBetween($from, $to);
        $wages     = ExpenseRepository::workerTotalBetween($from, $to);

        $expenses = $materials + $wages;

        return [
            'from'              => $from,
            'to'                => $to,
            'sales'             => $sales,
            'received'          => $received,
            'discounts'         => $discounts,
            'material_expenses' => $materials,
            'worker_expenses'   => $wages,
            'expenses'          => $expenses,
            'profit'            => $sales - $discounts - $expenses,
        ];
    }

    public static function topDebtors(int $limit = 5): array
    {
        $merchants = array_filter(
            MerchantRepository::all(),
            static fn (array $merchant): bool => $merchant['balance'] > 0,
        );

        usort(
            $merchants,
            static fn (array $a, array $b): int => $b['balance'] <=> $a['balance'],
        );

        return array_slice($merchants, 0, $limit);
    }

    public static function salesByJobType(string $from, string $to): array
    {
        return Database::select(
            "SELECT
                COALESCE(j.name, 'بدون نوع') AS name,
                j.unit AS unit,
                SUM(s.quantity) AS quantity,
                SUM(s.total) AS total
             FROM sales s
             LEFT JOIN job_types j ON j.id = s.job_type_id
             WHERE s.sale_date BETWEEN ? AND ?
             GROUP BY j.id
             ORDER BY total DESC",
            [$from, $to],
        );
    }

    public static function salesByMerchant(string $from, string $to): array
    {
        return Database::select(
            'SELECT m.name, SUM(s.total) AS total
             FROM sales s
             JOIN merchants m ON m.id = s.merchant_id
             WHERE s.sale_date BETWEEN ? AND ?
             GROUP BY m.id
             ORDER BY total DESC',
            [$from, $to],
        );
    }
}
