<?php

declare(strict_types=1);

namespace App;

final class PaymentController extends Controller
{
    public function index(): void
    {
        $filters = [
            'merchant_id' => Request::queryInt('merchant_id'),
            'from'        => Request::query('from'),
            'to'          => Request::query('to'),
        ];

        $this->view(
            'pages/payments',
            [
                'title'     => 'الدفعات',
                'pageName'  => 'payments',
                'payments'  => PaymentRepository::recent($filters),
                'merchants' => MerchantRepository::options(),
                'filters'   => $filters,
            ],
        );
    }

    public function store(): void
    {
        $validator = $this->validator();

        $merchantId = $validator->existingId('merchant_id', 'التاجر', 'merchants');
        $amount     = $validator->money('amount', 'المبلغ');
        $date       = $validator->date('payment_date', 'التاريخ');
        $method     = $validator->text('method', 'طريقة الدفع', false, 60);
        $note       = $validator->text('note', 'ملاحظة', false, 500);

        if (! $validator->passes()) {
            $this->rejectForm($validator, $this->backTo(), 'payment');
        }

        Database::transaction(
            static function () use ($merchantId, $amount, $date, $method, $note): void {
                $id = Database::insert(
                    'payments',
                    [
                        'merchant_id'  => $merchantId,
                        'amount'       => $amount,
                        'payment_date' => $date,
                        'method'       => $method ?: null,
                        'note'         => $note ?: null,
                        'created_by'   => Auth::id(),
                    ],
                );

                Audit::log(Audit::CREATED, 'دفعة', $id, 'دفعة بقيمة ' . Money::display($amount) . ' من التاجر رقم ' . $merchantId);
            },
        );

        $merchant = MerchantRepository::find($merchantId);

        $this->saved(
            'تم تسجيل دفعة ' . Money::display($amount) . '. المتبقي على الحساب: ' . Money::display($merchant['balance']),
            $this->backTo(),
        );
    }

    public function destroy(): void
    {
        $this->requireAdmin();

        $id      = $this->postInt('id');
        $payment = PaymentRepository::find($id);

        if ($payment === null) {
            $this->notFound('الدفعة غير موجودة.');
        }

        Database::transaction(
            static function () use ($id, $payment): void {
                Database::delete('payments', $id);

                Audit::log(Audit::DELETED, 'دفعة', $id, 'حذف دفعة بقيمة ' . Money::display((int) $payment['amount']));
            },
        );

        $this->saved('تم حذف الدفعة.', '/merchants/show?id=' . $payment['merchant_id']);
    }

    private function backTo(): string
    {
        $merchantId = $this->postInt('return_merchant_id');

        return $merchantId > 0 ? '/merchants/show?id=' . $merchantId : '/payments';
    }
}
