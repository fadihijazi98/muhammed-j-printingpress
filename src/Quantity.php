<?php

declare(strict_types=1);

namespace App;

/** Quantities (litres, metres, hours) allow up to three decimal places. */
final class Quantity
{
    public static function parse(?string $input): ?float
    {
        if ($input === null) {
            return null;
        }

        $normalised = Money::normaliseDigits($input);
        $normalised = str_replace([',', ' ', "\u{00A0}"], '', $normalised);

        if ($normalised === '' || ! preg_match('/^\d+(\.\d+)?$/', $normalised)) {
            return null;
        }

        return round((float) $normalised, 3);
    }

    public static function format(float $quantity): string
    {
        $rounded = round($quantity, 3);

        return $rounded == (int) $rounded
            ? (string) (int) $rounded
            : rtrim(rtrim(number_format($rounded, 3, '.', ''), '0'), '.');
    }
}
