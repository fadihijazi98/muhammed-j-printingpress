<?php

declare(strict_types=1);

namespace App;

final class SaleController extends Controller
{
    private const FORM_KEY = 'sale';

    public function index(): void
    {
        $filters = [
            'merchant_id' => Request::queryInt('merchant_id'),
            'from'        => Request::query('from'),
            'to'          => Request::query('to'),
        ];

        $this->view(
            'pages/sales',
            [
                'title'     => 'المبيعات',
                'pageName'  => 'sales',
                'sales'     => SaleRepository::recent($filters),
                'merchants' => MerchantRepository::options(),
                'filters'   => $filters,
            ],
        );
    }

    public function create(): void
    {
        $this->form(null, Request::queryInt('merchant_id'));
    }

    public function edit(): void
    {
        $sale = SaleRepository::find(Request::queryInt('id') ?? 0);

        if ($sale === null) {
            $this->notFound('عملية البيع غير موجودة.');
        }

        $this->form($sale, (int) $sale['merchant_id']);
    }

    public function show(): void
    {
        $sale = SaleRepository::find(Request::queryInt('id') ?? 0);

        if ($sale === null) {
            $this->notFound('عملية البيع غير موجودة.');
        }

        $id = (int) $sale['id'];

        $this->view(
            'pages/sale-show',
            [
                'title'         => 'تفاصيل عملية البيع',
                'pageName'      => 'sales',
                'sale'          => $sale,
                'materialLines' => SaleCostRepository::materialsFor($id),
                'workerLines'   => SaleCostRepository::workersFor($id),
            ],
        );
    }

    public function store(): void
    {
        [$sale, $materialLines, $workerLines] = $this->readForm();

        $id = Database::transaction(
            static function () use ($sale, $materialLines, $workerLines): int {
                $id = Database::insert('sales', $sale + ['created_by' => Auth::id()]);

                SaleCostRepository::replaceFor($id, $materialLines, $workerLines, $sale['sale_date']);

                Audit::log(
                    Audit::CREATED,
                    'بيع',
                    $id,
                    'بيع بقيمة ' . Money::display($sale['total']) . ' للتاجر رقم ' . $sale['merchant_id'],
                );

                return $id;
            },
        );

        $this->saved('تم حفظ عملية البيع.', '/sales/show?id=' . $id);
    }

    public function update(): void
    {
        $id       = $this->postInt('id');
        $existing = SaleRepository::find($id);

        if ($existing === null) {
            $this->notFound('عملية البيع غير موجودة.');
        }

        [$sale, $materialLines, $workerLines] = $this->readForm($id);

        Database::transaction(
            static function () use ($id, $sale, $materialLines, $workerLines): void {
                Database::update(
                    'sales',
                    $id,
                    $sale + [
                        'updated_by' => Auth::id(),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                );

                SaleCostRepository::replaceFor($id, $materialLines, $workerLines, $sale['sale_date']);

                Audit::log(Audit::UPDATED, 'بيع', $id, 'تعديل بيع إلى ' . Money::display($sale['total']));
            },
        );

        $this->saved('تم حفظ التعديلات.', '/sales/show?id=' . $id);
    }

    public function destroy(): void
    {
        $this->requireAdmin();

        $id   = $this->postInt('id');
        $sale = SaleRepository::find($id);

        if ($sale === null) {
            $this->notFound('عملية البيع غير موجودة.');
        }

        Database::transaction(
            static function () use ($id, $sale): void {
                /* The cost lines belong to this sale only, so they go with it. */
                Database::run('DELETE FROM material_purchases WHERE sale_id = ?', [$id]);
                Database::run('DELETE FROM worker_payments WHERE sale_id = ?', [$id]);
                Database::delete('sales', $id);

                Audit::log(Audit::DELETED, 'بيع', $id, 'حذف بيع بقيمة ' . Money::display((int) $sale['total']));
            },
        );

        $this->saved('تم حذف عملية البيع وتكاليفها.', '/merchants/show?id=' . $sale['merchant_id']);
    }

    private function form(?array $sale, ?int $merchantId): void
    {
        $id = $sale === null ? 0 : (int) $sale['id'];

        $this->view(
            'pages/sale-form',
            [
                'title'         => $sale === null ? 'تسجيل عملية بيع' : 'تعديل عملية البيع',
                'pageName'      => 'sales',
                'sale'          => $sale,
                'merchantId'    => $merchantId,
                'merchants'     => MerchantRepository::options(),
                'jobTypes'      => SettingsRepository::jobTypes(true),
                'materials'     => SettingsRepository::materials(true),
                'workers'       => SettingsRepository::workers(true),
                'scripts'       => ['/assets/sale-form.js'],
                'materialLines' => $id === 0 ? [] : SaleCostRepository::materialsFor($id),
                'workerLines'   => $id === 0 ? [] : SaleCostRepository::workersFor($id),
            ],
        );
    }

    /**
     * Reads the whole one-page form: the sale itself plus its cost lines.
     * Every total is taken from the field the user can edit, so an agreed
     * price or a supplier discount survives exactly as it was typed.
     *
     * @return array{0: array, 1: array, 2: array}
     */
    private function readForm(int $editingId = 0): array
    {
        $validator = $this->validator();

        $merchantId = $validator->existingId('merchant_id', 'التاجر', 'merchants');
        $jobTypeId  = $validator->existingId('job_type_id', 'الصنف', 'job_types');
        $quantity   = $validator->quantity('quantity', 'العدد');
        $unitPrice  = $validator->money('unit_price', 'سعر القطعة');
        $saleDate   = $validator->date('sale_date', 'التاريخ');
        $note       = $validator->text('note', 'ملاحظة', false, 500);

        $total = $validator->money('total', 'الإجمالي النهائي');

        $materialLines = $this->readMaterialLines($validator);
        $workerLines   = $this->readWorkerLines($validator);

        if (! $validator->passes()) {
            $back = $editingId > 0
                ? '/sales/edit?id=' . $editingId
                : '/sales/new' . ($merchantId > 0 ? '?merchant_id=' . $merchantId : '');

            $this->rejectForm($validator, $back, self::FORM_KEY);
        }

        $jobType = SettingsRepository::findJobType($jobTypeId);

        $sale = [
            'merchant_id' => $merchantId,
            'job_type_id' => $jobTypeId,
            'description' => $jobType['name'] ?? null,
            'quantity'    => $quantity,
            'unit_price'  => $unitPrice,
            'total'       => $total,
            'sale_date'   => $saleDate,
            'note'        => $note ?: null,
        ];

        return [$sale, $materialLines, $workerLines];
    }

    private function readMaterialLines(Validator $validator): array
    {
        $lines = [];

        foreach ($this->rows('materials') as $index => $row) {
            $prefix = 'materials.' . $index . '.';

            $materialId = $validator->existingIdIn($row, 'material_id', $prefix . 'material_id', 'المادة', 'materials');
            $quantity   = $validator->quantityIn($row, 'quantity', $prefix . 'quantity', 'كمية المادة');
            $unitCost   = $validator->moneyIn($row, 'unit_cost', $prefix . 'unit_cost', 'سعر وحدة المادة');
            $total      = $validator->moneyIn($row, 'total', $prefix . 'total', 'إجمالي تكلفة المادة');

            $lines[] = [
                'material_id' => $materialId,
                'quantity'    => $quantity,
                'unit_cost'   => $unitCost,
                'total'       => $total,
                'note'        => trim((string) ($row['note'] ?? '')),
            ];
        }

        return $lines;
    }

    private function readWorkerLines(Validator $validator): array
    {
        $lines = [];

        foreach ($this->rows('workers') as $index => $row) {
            $prefix = 'workers.' . $index . '.';

            $workerId   = $validator->existingIdIn($row, 'worker_id', $prefix . 'worker_id', 'العامل', 'workers');
            $hours      = $validator->quantityIn($row, 'hours', $prefix . 'hours', 'عدد الساعات');
            $hourlyRate = $validator->moneyIn($row, 'hourly_rate', $prefix . 'hourly_rate', 'أجرة الساعة');
            $total      = $validator->moneyIn($row, 'total', $prefix . 'total', 'إجمالي أجرة العامل');

            $lines[] = [
                'worker_id'   => $workerId,
                'hours'       => $hours,
                'hourly_rate' => $hourlyRate,
                'total'       => $total,
                'note'        => trim((string) ($row['note'] ?? '')),
            ];
        }

        return $lines;
    }

    /** Skips rows the user added and then left blank rather than removing. */
    private function rows(string $field): array
    {
        $rows = Request::post()[$field] ?? [];

        if (! is_array($rows)) {
            return [];
        }

        $kept = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $filled = array_filter(
                $row,
                static fn ($value): bool => is_string($value) && trim($value) !== '',
            );

            if ($filled !== []) {
                $kept[$index] = $row;
            }
        }

        return $kept;
    }
}
