<?php

declare(strict_types=1);

namespace App;

final class MaterialExpenseController extends Controller
{
    public function index(): void
    {
        $filters = [
            'material_id' => Request::queryInt('material_id'),
            'from'        => Request::query('from'),
            'to'          => Request::query('to'),
        ];

        $this->view(
            'pages/material-expenses',
            [
                'title'     => 'مصاريف المواد',
                'pageName'  => 'materials',
                'purchases' => ExpenseRepository::materialPurchases($filters),
                'materials' => SettingsRepository::materials(true),
                'filters'   => $filters,
            ],
        );
    }

    public function store(): void
    {
        $validator = $this->validator();

        $materialId = $validator->existingId('material_id', 'المادة', 'materials');
        $quantity   = $validator->quantity('quantity', 'الكمية');
        $unitCost   = $validator->money('unit_cost', 'سعر الوحدة');
        $date       = $validator->date('purchase_date', 'التاريخ');
        $supplier   = $validator->text('supplier', 'اسم المورّد', false, 120);
        $note       = $validator->text('note', 'ملاحظة', false, 500);

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/material-expenses');
        }

        $total = Money::multiply($quantity, $unitCost);

        Database::transaction(
            static function () use ($materialId, $quantity, $unitCost, $total, $date, $supplier, $note): void {
                $id = Database::insert(
                    'material_purchases',
                    [
                        'material_id'   => $materialId,
                        'quantity'      => $quantity,
                        'unit_cost'     => $unitCost,
                        'total'         => $total,
                        'purchase_date' => $date,
                        'supplier'      => $supplier ?: null,
                        'note'          => $note ?: null,
                        'created_by'    => Auth::id(),
                    ],
                );

                Audit::log(Audit::CREATED, 'شراء مواد', $id, 'شراء مواد بقيمة ' . Money::display($total));
            },
        );

        $this->saved('تم تسجيل المصروف بقيمة ' . Money::display($total) . '.', '/material-expenses');
    }

    public function destroy(): void
    {
        $this->requireAdmin();

        $id       = $this->postInt('id');
        $purchase = ExpenseRepository::findMaterialPurchase($id);

        if ($purchase === null) {
            $this->notFound('المصروف غير موجود.');
        }

        Database::transaction(
            static function () use ($id, $purchase): void {
                Database::delete('material_purchases', $id);

                Audit::log(Audit::DELETED, 'شراء مواد', $id, 'حذف مصروف بقيمة ' . Money::display((int) $purchase['total']));
            },
        );

        $this->saved('تم حذف المصروف.', '/material-expenses');
    }
}
