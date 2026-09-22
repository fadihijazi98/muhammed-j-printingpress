<?php

declare(strict_types=1);

namespace App;

/**
 * A page can hold several forms. Only the one that was actually rejected should
 * show error messages and remembered input; the rest render clean.
 */
final class FormState
{
    public function __construct(
        private readonly string $activeKey,
        private readonly array $errors,
        private readonly array $old,
    ) {
    }

    public function errors(string $key = ''): array
    {
        return $this->activeKey === $key ? $this->errors : [];
    }

    public function old(string $key = ''): array
    {
        return $this->activeKey === $key ? $this->old : [];
    }
}
