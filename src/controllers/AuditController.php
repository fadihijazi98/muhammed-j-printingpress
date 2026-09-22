<?php

declare(strict_types=1);

namespace App;

final class AuditController extends Controller
{
    public function index(): void
    {
        $this->view(
            'pages/audit',
            [
                'title'    => 'سجل العمليات',
                'pageName' => 'audit',
                'entries'  => AuditRepository::recent(),
            ],
        );
    }
}
