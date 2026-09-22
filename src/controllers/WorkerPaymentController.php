<?php

declare(strict_types=1);

namespace App;

final class WorkerPaymentController extends Controller
{
    public function index(): void
    {
        $filters = [
            'worker_id' => Request::queryInt('worker_id'),
            'from'      => Request::query('from'),
            'to'        => Request::query('to'),
        ];

        $this->view(
            'pages/worker-payments',
            [
                'title'    => 'أجور العمال',
                'pageName' => 'workers',
                'payments' => ExpenseRepository::workerPayments($filters),
                'workers'  => SettingsRepository::workers(true),
                'filters'  => $filters,
            ],
        );
    }

    public function store(): void
    {
        $validator = $this->validator();

        $workerId   = $validator->existingId('worker_id', 'العامل', 'workers');
        $hours      = $validator->quantity('hours', 'عدد الساعات');
        $hourlyRate = $validator->money('hourly_rate', 'أجرة الساعة');
        $date       = $validator->date('work_date', 'التاريخ');
        $note       = $validator->text('note', 'ملاحظة', false, 500);

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/worker-payments');
        }

        $total = Money::multiply($hours, $hourlyRate);

        Database::transaction(
            static function () use ($workerId, $hours, $hourlyRate, $total, $date, $note): void {
                $id = Database::insert(
                    'worker_payments',
                    [
                        'worker_id'   => $workerId,
                        'hours'       => $hours,
                        'hourly_rate' => $hourlyRate,
                        'total'       => $total,
                        'work_date'   => $date,
                        'note'        => $note ?: null,
                        'created_by'  => Auth::id(),
                    ],
                );

                Audit::log(Audit::CREATED, 'أجرة عامل', $id, 'أجرة بقيمة ' . Money::display($total));
            },
        );

        $this->saved('تم تسجيل الأجرة بقيمة ' . Money::display($total) . '.', '/worker-payments');
    }

    public function destroy(): void
    {
        $this->requireAdmin();

        $id      = $this->postInt('id');
        $payment = ExpenseRepository::findWorkerPayment($id);

        if ($payment === null) {
            $this->notFound('السجل غير موجود.');
        }

        Database::transaction(
            static function () use ($id, $payment): void {
                Database::delete('worker_payments', $id);

                Audit::log(Audit::DELETED, 'أجرة عامل', $id, 'حذف أجرة بقيمة ' . Money::display((int) $payment['total']));
            },
        );

        $this->saved('تم حذف السجل.', '/worker-payments');
    }
}
