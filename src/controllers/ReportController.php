<?php

declare(strict_types=1);

namespace App;

final class ReportController extends Controller
{
    public function index(): void
    {
        $from = Request::query('from', date('Y-m-01'));
        $to   = Request::query('to', date('Y-m-t'));

        if (! $this->isDate($from)) {
            $from = date('Y-m-01');
        }

        if (! $this->isDate($to)) {
            $to = date('Y-m-t');
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->view(
            'pages/reports',
            [
                'title'             => 'التقارير',
                'pageName'          => 'reports',
                'summary'           => ReportRepository::summary($from, $to),
                'salesByJobType'    => ReportRepository::salesByJobType($from, $to),
                'salesByMerchant'   => ReportRepository::salesByMerchant($from, $to),
                'materialBreakdown' => ExpenseRepository::materialBreakdown($from, $to),
                'workerBreakdown'   => ExpenseRepository::workerBreakdown($from, $to),
                'debtors'           => ReportRepository::topDebtors(100),
                'from'              => $from,
                'to'                => $to,
            ],
        );
    }

    private function isDate(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }
}
