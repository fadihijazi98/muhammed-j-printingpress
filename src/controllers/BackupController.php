<?php

declare(strict_types=1);

namespace App;

/**
 * Everything lives in one SQLite file on one desktop, so a one-click copy is
 * the difference between a bad day and losing the whole ledger.
 */
final class BackupController extends Controller
{
    public function download(): void
    {
        $source = Config::databasePath();

        if (! is_file($source)) {
            $this->notFound('ملف قاعدة البيانات غير موجود.');
        }

        $copy = tempnam(sys_get_temp_dir(), 'mjbackup');

        /* VACUUM INTO copies a consistent snapshot even while the app is in use. */
        unlink($copy);
        Database::connection()->exec("VACUUM INTO '" . str_replace("'", "''", $copy) . "'");

        Audit::log(Audit::CREATED, 'نسخة احتياطية', null, 'تنزيل نسخة احتياطية');

        $filename = 'backup-' . date('Y-m-d-His') . '.sqlite';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($copy));
        header('Cache-Control: no-store');

        readfile($copy);
        unlink($copy);

        exit;
    }
}
