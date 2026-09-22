<?php

declare(strict_types=1);

namespace App;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $content = self::capture($template, $data);

        $layoutData = $data + [
            'content'  => $content,
            'flashes'  => Session::takeFlashes(),
            'user'     => Auth::user(),
            'pageName' => $data['pageName'] ?? '',
            'title'    => $data['title'] ?? Config::APP_NAME,
            'scripts'  => $data['scripts'] ?? [],
        ];

        echo self::capture('layout', $layoutData);
    }

    /** Renders a template without the surrounding layout. */
    public static function capture(string $template, array $data = []): string
    {
        $file = Config::basePath('views/' . $template . '.php');

        if (! is_file($file)) {
            throw new \RuntimeException('القالب غير موجود: ' . $template);
        }

        extract($data, EXTR_SKIP);

        ob_start();

        require $file;

        return (string) ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function merchantLink(int $merchantId, ?string $name): string
    {
        $label = self::e($name);

        return Auth::isAdmin()
            ? '<a href="/merchants/show?id=' . $merchantId . '">' . $label . '</a>'
            : $label;
    }

    public static function money(int $minor): string
    {
        return Money::display($minor);
    }

    /** 2026-09-22 → 22/09/2026, which is how the date reads on paper here. */
    public static function date(?string $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', substr($date, 0, 10));

        return $parsed === false ? $date : $parsed->format('d/m/Y');
    }

    public static function dateTime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        return $parsed === false ? $value : $parsed->format('d/m/Y H:i');
    }
}
