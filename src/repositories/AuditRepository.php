<?php

declare(strict_types=1);

namespace App;

final class AuditRepository
{
    public static function recent(int $limit = 300): array
    {
        return Database::select(
            'SELECT * FROM audit_log ORDER BY id DESC LIMIT ' . $limit,
        );
    }
}
