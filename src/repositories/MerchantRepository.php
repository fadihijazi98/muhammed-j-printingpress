<?php

declare(strict_types=1);

namespace App;

final class MerchantRepository
{
    /**
     * Balance is what the merchant still owes:
     * total sales − discounts given − payments received.
     */
    private const BALANCE_SELECT = "
        SELECT
            m.*,
            COALESCE((SELECT SUM(total)  FROM sales     WHERE merchant_id = m.id), 0) AS total_sales,
            COALESCE((SELECT SUM(amount) FROM payments  WHERE merchant_id = m.id), 0) AS total_paid,
            COALESCE((SELECT SUM(amount) FROM discounts WHERE merchant_id = m.id), 0) AS total_discount
        FROM merchants m
    ";

    public static function all(?string $search = null, bool $activeOnly = false): array
    {
        $where    = [];
        $bindings = [];

        if ($activeOnly) {
            $where[] = 'm.is_active = 1';
        }

        if ($search !== null && trim($search) !== '') {
            $where[]    = '(m.name LIKE ? OR m.phone LIKE ?)';
            $bindings[] = '%' . trim($search) . '%';
            $bindings[] = '%' . trim($search) . '%';
        }

        $sql = self::BALANCE_SELECT
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY m.is_active DESC, m.name COLLATE NOCASE';

        return array_map(self::withBalance(...), Database::select($sql, $bindings));
    }

    public static function find(int $id): ?array
    {
        $row = Database::selectOne(self::BALANCE_SELECT . ' WHERE m.id = ?', [$id]);

        return $row === null ? null : self::withBalance($row);
    }

    public static function options(): array
    {
        return Database::select('SELECT id, name FROM merchants WHERE is_active = 1 ORDER BY name COLLATE NOCASE');
    }

    public static function totalOutstanding(): int
    {
        $sales     = (int) Database::scalar('SELECT COALESCE(SUM(total), 0)  FROM sales');
        $paid      = (int) Database::scalar('SELECT COALESCE(SUM(amount), 0) FROM payments');
        $discounts = (int) Database::scalar('SELECT COALESCE(SUM(amount), 0) FROM discounts');

        return $sales - $paid - $discounts;
    }

    /** All account movements for one merchant, newest first, as a single timeline. */
    public static function ledger(int $merchantId): array
    {
        $rows = Database::select(
            "SELECT
                'sale' AS kind,
                s.id AS id,
                s.sale_date AS date,
                s.total AS amount,
                COALESCE(j.name, s.description, 'بيع') AS label,
                s.note AS note,
                s.quantity AS quantity,
                s.unit_price AS unit_price,
                j.unit AS unit,
                u.name AS created_by_name,
                s.created_at AS created_at
             FROM sales s
             LEFT JOIN job_types j ON j.id = s.job_type_id
             LEFT JOIN users u ON u.id = s.created_by
             WHERE s.merchant_id = :id

             UNION ALL

             SELECT
                'payment', p.id, p.payment_date, p.amount,
                COALESCE(p.method, 'دفعة'),
                p.note, NULL, NULL, NULL,
                u.name, p.created_at
             FROM payments p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.merchant_id = :id

             UNION ALL

             SELECT
                'discount', d.id, d.discount_date, d.amount,
                COALESCE(d.reason, 'خصم'),
                NULL, NULL, NULL, NULL,
                u.name, d.created_at
             FROM discounts d
             LEFT JOIN users u ON u.id = d.created_by
             WHERE d.merchant_id = :id

             ORDER BY date DESC, created_at DESC",
            ['id' => $merchantId],
        );

        return $rows;
    }

    private static function withBalance(array $row): array
    {
        $row['total_sales']    = (int) $row['total_sales'];
        $row['total_paid']     = (int) $row['total_paid'];
        $row['total_discount'] = (int) $row['total_discount'];
        $row['balance']        = $row['total_sales'] - $row['total_paid'] - $row['total_discount'];

        return $row;
    }
}
