<?php

declare(strict_types=1);

namespace App;

/** Discounts reduce what a merchant owes, so they are a manager decision only. */
final class DiscountController extends Controller
{
    public function index(): void
    {
        $this->view(
            'pages/discounts',
            [
                'title'     => 'الخصومات',
                'pageName'  => 'discounts',
                'discounts' => DiscountRepository::recent(),
                'merchants' => MerchantRepository::options(),
            ],
        );
    }

    public function store(): void
    {
        $validator = $this->validator();

        $merchantId = $validator->existingId('merchant_id', 'التاجر', 'merchants');
        $amount     = $validator->money('amount', 'قيمة الخصم');
        $date       = $validator->date('discount_date', 'التاريخ');
        $reason     = $validator->text('reason', 'سبب الخصم', false, 200);

        if (! $validator->passes()) {
            $this->rejectForm($validator, $this->backTo(), 'discount');
        }

        Database::transaction(
            static function () use ($merchantId, $amount, $date, $reason): void {
                $id = Database::insert(
                    'discounts',
                    [
                        'merchant_id'   => $merchantId,
                        'amount'        => $amount,
                        'discount_date' => $date,
                        'reason'        => $reason ?: null,
                        'created_by'    => Auth::id(),
                    ],
                );

                Audit::log(Audit::CREATED, 'خصم', $id, 'خصم بقيمة ' . Money::display($amount) . ' للتاجر رقم ' . $merchantId);
            },
        );

        $this->saved('تم تسجيل الخصم.', $this->backTo());
    }

    public function destroy(): void
    {
        $id       = $this->postInt('id');
        $discount = DiscountRepository::find($id);

        if ($discount === null) {
            $this->notFound('الخصم غير موجود.');
        }

        Database::transaction(
            static function () use ($id, $discount): void {
                Database::delete('discounts', $id);

                Audit::log(Audit::DELETED, 'خصم', $id, 'حذف خصم بقيمة ' . Money::display((int) $discount['amount']));
            },
        );

        $this->saved('تم حذف الخصم.', '/merchants/show?id=' . $discount['merchant_id']);
    }

    private function backTo(): string
    {
        $merchantId = $this->postInt('return_merchant_id');

        return $merchantId > 0 ? '/merchants/show?id=' . $merchantId : '/discounts';
    }
}
