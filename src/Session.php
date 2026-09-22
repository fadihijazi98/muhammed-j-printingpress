<?php

declare(strict_types=1);

namespace App;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params(
            [
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ],
        );

        session_name('MJPRESS');
        session_start();
    }

    public static function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
    }

    public static function takeFlashes(): array
    {
        $flashes = $_SESSION['flash'] ?? [];

        unset($_SESSION['flash']);

        return $flashes;
    }

    /** Keeps a rejected form on screen with its errors and the values already typed. */
    public static function flashForm(array $errors, array $old, string $formKey = ''): void
    {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old']    = $old;
        $_SESSION['form_key']    = $formKey;
    }

    public static function takeErrors(): array
    {
        $errors = $_SESSION['form_errors'] ?? [];

        unset($_SESSION['form_errors']);

        return $errors;
    }

    public static function takeOld(): array
    {
        $old = $_SESSION['form_old'] ?? [];

        unset($_SESSION['form_old']);

        return $old;
    }

    /** Which form on the page was rejected, so pages with several forms stay readable. */
    public static function takeFormKey(): string
    {
        $key = $_SESSION['form_key'] ?? '';

        unset($_SESSION['form_key']);

        return $key;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function csrfMatches(?string $token): bool
    {
        return is_string($token)
            && ! empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
