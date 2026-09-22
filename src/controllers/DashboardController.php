<?php

declare(strict_types=1);

namespace App;

final class DashboardController extends Controller
{
    public function index(): void
    {
        if (! Auth::isAdmin()) {
            Response::redirect('/sales');
        }

        $today      = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $this->view(
            'pages/dashboard',
            [
                'title'      => 'الرئيسية',
                'pageName'   => 'dashboard',
                'today'      => ReportRepository::summary($today, $today),
                'month'      => ReportRepository::summary($monthStart, $monthEnd),
                'outstanding' => MerchantRepository::totalOutstanding(),
                'debtors'    => ReportRepository::topDebtors(),
                'recentSales' => SaleRepository::recent([], 8),
            ],
        );
    }
}
