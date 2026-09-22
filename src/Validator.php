<?php

declare(strict_types=1);

namespace App;

/**
 * Collects Arabic error messages for one form. Every rule returns the cleaned
 * value so controllers work with parsed data rather than raw input.
 */
final class Validator
{
    private array $errors = [];

    public function __construct(private readonly array $input)
    {
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fail(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function raw(string $field): string
    {
        return trim((string) ($this->input[$field] ?? ''));
    }

    public function text(string $field, string $label, bool $required = true, int $max = 200): string
    {
        $value = $this->raw($field);

        if ($value === '') {
            if ($required) {
                $this->fail($field, 'الرجاء إدخال ' . $label . '.');
            }

            return '';
        }

        if (mb_strlen($value) > $max) {
            $this->fail($field, $label . ' طويل جداً (الحد الأقصى ' . $max . ' حرف).');
        }

        return $value;
    }

    public function money(string $field, string $label, bool $required = true, bool $allowZero = false): int
    {
        return $this->checkMoney($this->raw($field), $field, $label, $required, $allowZero);
    }

    public function quantity(string $field, string $label): float
    {
        return $this->checkQuantity($this->raw($field), $field, $label);
    }

    public function date(string $field, string $label): string
    {
        $raw = Money::normaliseDigits($this->raw($field));

        if ($raw === '') {
            $this->fail($field, 'الرجاء إدخال ' . $label . '.');

            return date('Y-m-d');
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $raw);

        if ($parsed === false || $parsed->format('Y-m-d') !== $raw) {
            $this->fail($field, $label . ' غير صحيح.');

            return date('Y-m-d');
        }

        return $raw;
    }

    /** Confirms the selected id exists in the given table and is still active. */
    public function existingId(string $field, string $label, string $table, bool $activeOnly = true): int
    {
        return $this->checkExistingId($this->raw($field), $field, $label, $table, $activeOnly);
    }

    public function email(string $field, string $label, ?int $ignoreUserId = null): string
    {
        $value = mb_strtolower($this->raw($field));

        if ($value === '') {
            $this->fail($field, 'الرجاء إدخال ' . $label . '.');

            return '';
        }

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->fail($field, $label . ' غير صحيح.');

            return $value;
        }

        $existing = Database::selectOne('SELECT id FROM users WHERE email = ?', [$value]);

        if ($existing !== null && (int) $existing['id'] !== $ignoreUserId) {
            $this->fail($field, 'هذا البريد مستخدم من قبل حساب آخر.');
        }

        return $value;
    }

    public function choice(string $field, string $label, array $allowed): string
    {
        $value = $this->raw($field);

        if (! in_array($value, $allowed, true)) {
            $this->fail($field, 'الرجاء اختيار ' . $label . '.');

            return $allowed[0];
        }

        return $value;
    }

    /*
     * The "…In" variants read a value out of one row of a repeated group and
     * file any error under a key such as "materials.0.total", so the form can
     * highlight the exact line that is wrong.
     */

    public function moneyIn(array $row, string $field, string $errorKey, string $label, bool $allowZero = true): int
    {
        return $this->checkMoney(trim((string) ($row[$field] ?? '')), $errorKey, $label, true, $allowZero);
    }

    public function quantityIn(array $row, string $field, string $errorKey, string $label): float
    {
        return $this->checkQuantity(trim((string) ($row[$field] ?? '')), $errorKey, $label);
    }

    public function existingIdIn(array $row, string $field, string $errorKey, string $label, string $table): int
    {
        return $this->checkExistingId(trim((string) ($row[$field] ?? '')), $errorKey, $label, $table);
    }

    private function checkMoney(string $raw, string $errorKey, string $label, bool $required, bool $allowZero): int
    {
        if ($raw === '') {
            if ($required) {
                $this->fail($errorKey, 'الرجاء إدخال ' . $label . '.');
            }

            return 0;
        }

        $amount = Money::parse($raw);

        if ($amount === null) {
            $this->fail($errorKey, $label . ' يجب أن يكون رقماً.');

            return 0;
        }

        if ($amount < 0) {
            $this->fail($errorKey, $label . ' لا يمكن أن يكون بالسالب.');

            return 0;
        }

        if ($amount === 0 && ! $allowZero) {
            $this->fail($errorKey, $label . ' يجب أن يكون أكبر من صفر.');
        }

        return $amount;
    }

    private function checkQuantity(string $raw, string $errorKey, string $label): float
    {
        if ($raw === '') {
            $this->fail($errorKey, 'الرجاء إدخال ' . $label . '.');

            return 0.0;
        }

        $quantity = Quantity::parse($raw);

        if ($quantity === null) {
            $this->fail($errorKey, $label . ' يجب أن تكون رقماً.');

            return 0.0;
        }

        if ($quantity <= 0) {
            $this->fail($errorKey, $label . ' يجب أن تكون أكبر من صفر.');
        }

        return $quantity;
    }

    private function checkExistingId(string $raw, string $errorKey, string $label, string $table, bool $activeOnly = true): int
    {
        $raw = Money::normaliseDigits($raw);

        if ($raw === '' || ! ctype_digit($raw)) {
            $this->fail($errorKey, 'الرجاء اختيار ' . $label . '.');

            return 0;
        }

        $sql = 'SELECT id FROM ' . $table . ' WHERE id = ?' . ($activeOnly ? ' AND is_active = 1' : '');

        if (Database::selectOne($sql, [(int) $raw]) === null) {
            $this->fail($errorKey, $label . ' غير موجود.');

            return 0;
        }

        return (int) $raw;
    }
}
