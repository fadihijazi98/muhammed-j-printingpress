<?php

declare(strict_types=1);

namespace App;

/**
 * All money is stored as whole أغورة (1/100 of a shekel) so that totals never
 * drift by a fraction of a penny the way floating point amounts do.
 */
final class Money
{
    public const SYMBOL = '₪';

    /** Returns null when the text is not a usable amount. */
    public static function parse(?string $input): ?int
    {
        if ($input === null) {
            return null;
        }

        $normalised = self::normaliseDigits($input);
        $normalised = str_replace([',', ' ', "\u{00A0}"], '', $normalised);

        if ($normalised === '' || ! preg_match('/^-?\d+(\.\d+)?$/', $normalised)) {
            return null;
        }

        return (int) round(((float) $normalised) * 100);
    }

    public static function format(int $minor): string
    {
        $sign  = $minor < 0 ? '-' : '';
        $minor = abs($minor);

        return $sign . number_format(intdiv($minor, 100)) . '.' . str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function display(int $minor): string
    {
        return self::SYMBOL . ' ' . self::format($minor);
    }

    /** Multiplies a decimal quantity by a per-unit price, rounded to the nearest أغورة. */
    public static function multiply(float $quantity, int $unitPrice): int
    {
        return (int) round($quantity * $unitPrice);
    }

    /** Accepts Arabic-Indic digits, which Arabic keyboards produce by default. */
    public static function normaliseDigits(string $input): string
    {
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫', '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return trim(str_replace($arabic, $western, $input));
    }
}
