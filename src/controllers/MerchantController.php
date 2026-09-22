<?php

declare(strict_types=1);

namespace App;

final class MerchantController extends Controller
{
    public function index(): void
    {
        $search = Request::query('q');

        $this->view(
            'pages/merchants',
            [
                'title'     => 'التجار',
                'pageName'  => 'merchants',
                'merchants' => MerchantRepository::all($search),
                'search'    => $search,
            ],
        );
    }

    public function show(): void
    {
        $id       = Request::queryInt('id') ?? 0;
        $merchant = MerchantRepository::find($id);

        if ($merchant === null) {
            $this->notFound('التاجر غير موجود.');
        }

        $this->view(
            'pages/merchant-show',
            [
                'title'    => $merchant['name'],
                'pageName' => 'merchants',
                'merchant' => $merchant,
                'ledger'   => MerchantRepository::ledger($id),
            ],
        );
    }

    public function store(): void
    {
        $validator = $this->validator();

        $name  = $validator->text('name', 'اسم التاجر');
        $phone = $validator->text('phone', 'رقم الهاتف', false, 40);
        $note  = $validator->text('note', 'ملاحظة', false, 500);

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/merchants', 'merchant-create');
        }

        $id = Database::transaction(
            static function () use ($name, $phone, $note): int {
                $id = Database::insert(
                    'merchants',
                    [
                        'name'       => $name,
                        'phone'      => $phone ?: null,
                        'note'       => $note ?: null,
                        'created_by' => Auth::id(),
                    ],
                );

                Audit::log(Audit::CREATED, 'تاجر', $id, 'إضافة تاجر: ' . $name);

                return $id;
            },
        );

        $this->saved('تم إضافة التاجر.', '/merchants/show?id=' . $id);
    }

    public function update(): void
    {
        $id       = $this->postInt('id');
        $merchant = MerchantRepository::find($id);

        if ($merchant === null) {
            $this->notFound('التاجر غير موجود.');
        }

        $validator = $this->validator();

        $name     = $validator->text('name', 'اسم التاجر');
        $phone    = $validator->text('phone', 'رقم الهاتف', false, 40);
        $note     = $validator->text('note', 'ملاحظة', false, 500);
        $isActive = isset(Request::post()['is_active']) ? 1 : 0;

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/merchants/show?id=' . $id, 'merchant-edit');
        }

        Database::transaction(
            static function () use ($id, $name, $phone, $note, $isActive): void {
                Database::update(
                    'merchants',
                    $id,
                    [
                        'name'       => $name,
                        'phone'      => $phone ?: null,
                        'note'       => $note ?: null,
                        'is_active'  => $isActive,
                        'updated_by' => Auth::id(),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                );

                Audit::log(Audit::UPDATED, 'تاجر', $id, 'تعديل تاجر: ' . $name);
            },
        );

        $this->saved('تم حفظ بيانات التاجر.', '/merchants/show?id=' . $id);
    }

    public function destroy(): void
    {
        $this->requireAdmin();

        $id       = $this->postInt('id');
        $merchant = MerchantRepository::find($id);

        if ($merchant === null) {
            $this->notFound('التاجر غير موجود.');
        }

        $hasRecords = $merchant['total_sales'] !== 0
            || $merchant['total_paid'] !== 0
            || $merchant['total_discount'] !== 0;

        if ($hasRecords) {
            Session::flash('لا يمكن حذف تاجر له حركات مالية. يمكنك إيقافه بدل الحذف.', 'error');

            Response::redirect('/merchants/show?id=' . $id);
        }

        Database::transaction(
            static function () use ($id, $merchant): void {
                Database::delete('merchants', $id);

                Audit::log(Audit::DELETED, 'تاجر', $id, 'حذف تاجر: ' . $merchant['name']);
            },
        );

        $this->saved('تم حذف التاجر.', '/merchants');
    }
}
