<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';

    private static ?array $cachedUser = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::selectOne(
            'SELECT * FROM users WHERE email = ? AND is_active = 1',
            [mb_strtolower(trim($email))],
        );

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return false;
        }

        /* A new session id guards against session fixation, but it can only be
           issued while the response headers are still open. */
        if (session_status() === PHP_SESSION_ACTIVE && ! headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = (int) $user['id'];
        self::$cachedUser    = $user;

        return true;
    }

    public static function logout(): void
    {
        $_SESSION         = [];
        self::$cachedUser = null;

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $id = $_SESSION['user_id'] ?? null;

        if ($id === null) {
            return null;
        }

        $user = Database::selectOne('SELECT * FROM users WHERE id = ? AND is_active = 1', [(int) $id]);

        if ($user === null) {
            unset($_SESSION['user_id']);

            return null;
        }

        return self::$cachedUser = $user;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && $user['role'] === self::ROLE_ADMIN;
    }

    /** Staff have no dashboard, so each role has its own landing page. */
    public static function homePath(): string
    {
        return self::isAdmin() ? '/' : '/sales';
    }

    public static function roleLabel(string $role): string
    {
        return $role === self::ROLE_ADMIN ? 'مدير' : 'موظف';
    }
}
