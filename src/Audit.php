<?php

declare(strict_types=1);

namespace App;

final class Audit
{
    public const CREATED = 'إضافة';
    public const UPDATED = 'تعديل';
    public const DELETED = 'حذف';

    public static function log(string $action, string $entity, ?int $entityId, string $summary): void
    {
        $user = Auth::user();

        Database::insert(
            'audit_log',
            [
                'user_id'   => $user === null ? null : (int) $user['id'],
                'user_name' => $user === null ? 'غير معروف' : $user['name'],
                'action'    => $action,
                'entity'    => $entity,
                'entity_id' => $entityId,
                'summary'   => $summary,
            ],
        );
    }
}
