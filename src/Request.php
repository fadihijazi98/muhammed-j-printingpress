<?php

declare(strict_types=1);

namespace App;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = '/' . trim((string) $path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public static function post(): array
    {
        return $_POST;
    }

    public static function query(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public static function queryInt(string $key, ?int $default = null): ?int
    {
        $value = self::query($key);

        return $value !== null && ctype_digit($value) ? (int) $value : $default;
    }
}
