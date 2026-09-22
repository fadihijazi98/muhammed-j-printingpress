<?php

declare(strict_types=1);

namespace App;

require_once __DIR__ . '/Config.php';

spl_autoload_register(
    static function (string $class): void {
        if (! str_starts_with($class, 'App\\')) {
            return;
        }

        $relative = str_replace('\\', '/', substr($class, 4));

        foreach (['src/', 'src/repositories/', 'src/controllers/'] as $directory) {
            $file = Config::basePath($directory . $relative . '.php');

            if (is_file($file)) {
                require_once $file;

                return;
            }
        }
    },
);

date_default_timezone_set(Config::timezone());
mb_internal_encoding('UTF-8');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', Config::errorLogPath());
error_reporting(E_ALL);

if (! is_dir(dirname(Config::errorLogPath()))) {
    mkdir(dirname(Config::errorLogPath()), 0775, true);
}

Session::start();

Database::migrate();
Seeder::run();
