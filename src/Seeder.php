<?php

declare(strict_types=1);

namespace App;

/**
 * First-run data. Only ever inserted into an empty table, so a real password
 * change or a deleted example row is never silently restored.
 */
final class Seeder
{
    public static function run(): void
    {
        if ((int) Database::scalar('SELECT COUNT(*) FROM users') === 0) {
            Database::insert(
                'users',
                [
                    'name'          => 'محمد (مدير)',
                    'email'         => 'muhammed@admin.com',
                    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                    'role'          => Auth::ROLE_ADMIN,
                ],
            );

            Database::insert(
                'users',
                [
                    'name'          => 'موظف',
                    'email'         => 'any@staff.com',
                    'password_hash' => password_hash('staff_password', PASSWORD_DEFAULT),
                    'role'          => Auth::ROLE_STAFF,
                ],
            );
        }

        if ((int) Database::scalar('SELECT COUNT(*) FROM materials') === 0) {
            Database::insert(
                'materials',
                [
                    'name'              => 'دهان',
                    'unit'              => 'لتر',
                    'default_unit_cost' => 2500,
                ],
            );
        }

        if ((int) Database::scalar('SELECT COUNT(*) FROM workers') === 0) {
            Database::insert(
                'workers',
                [
                    'name'        => 'عامل',
                    'hourly_rate' => 2000,
                ],
            );
        }

        if ((int) Database::scalar('SELECT COUNT(*) FROM job_types') === 0) {
            Database::insert(
                'job_types',
                [
                    'name'               => 'طباعة',
                    'unit'               => 'قطعة',
                    'default_unit_price' => 2500,
                ],
            );
        }
    }
}
