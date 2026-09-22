<?php

declare(strict_types=1);

namespace App;

final class Config
{
    public const APP_NAME = 'مطبعة محمد';

    public static function basePath(string $append = ''): string
    {
        $base = dirname(__DIR__);

        return $append === '' ? $base : $base . '/' . ltrim($append, '/');
    }

    public static function databasePath(): string
    {
        return getenv('APP_DATABASE') ?: self::basePath('data/app.sqlite');
    }

    public static function errorLogPath(): string
    {
        return self::basePath('data/error.log');
    }

    public static function timezone(): string
    {
        return 'Asia/Hebron';
    }
}
