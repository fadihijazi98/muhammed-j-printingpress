<?php

declare(strict_types=1);

namespace App;

/** The three configurable lists: job types, materials and workers. */
final class SettingsRepository
{
    public static function jobTypes(bool $activeOnly = false): array
    {
        return Database::select(
            'SELECT * FROM job_types' . ($activeOnly ? ' WHERE is_active = 1' : '')
            . ' ORDER BY is_active DESC, name COLLATE NOCASE',
        );
    }

    public static function findJobType(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM job_types WHERE id = ?', [$id]);
    }

    public static function materials(bool $activeOnly = false): array
    {
        return Database::select(
            'SELECT * FROM materials' . ($activeOnly ? ' WHERE is_active = 1' : '')
            . ' ORDER BY is_active DESC, name COLLATE NOCASE',
        );
    }

    public static function findMaterial(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM materials WHERE id = ?', [$id]);
    }

    public static function workers(bool $activeOnly = false): array
    {
        return Database::select(
            'SELECT * FROM workers' . ($activeOnly ? ' WHERE is_active = 1' : '')
            . ' ORDER BY is_active DESC, name COLLATE NOCASE',
        );
    }

    public static function findWorker(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM workers WHERE id = ?', [$id]);
    }

    public static function users(): array
    {
        return Database::select('SELECT * FROM users ORDER BY is_active DESC, name COLLATE NOCASE');
    }

    public static function findUser(int $id): ?array
    {
        return Database::selectOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function countActiveAdmins(?int $excludingUserId = null): int
    {
        $sql      = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1";
        $bindings = [];

        if ($excludingUserId !== null) {
            $sql       .= ' AND id != ?';
            $bindings[] = $excludingUserId;
        }

        return (int) Database::scalar($sql, $bindings);
    }

    public static function isInUse(string $table, string $column, int $id): bool
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $column . ' = ?', [$id]) > 0;
    }
}
