<?php

declare(strict_types=1);

namespace App;

/**
 * Small HTML builders so every form across the app looks and behaves the same:
 * same error styling, same "keep what the user typed" behaviour.
 */
final class Form
{
    public static function open(string $action, array $options = []): string
    {
        $attributes = ' method="post" action="' . View::e($action) . '"';

        if (! empty($options['confirm'])) {
            $attributes .= ' data-confirm="' . View::e($options['confirm']) . '"';
        }

        if (! empty($options['live_total'])) {
            $attributes .= ' data-total-form';
        }

        if (! empty($options['class'])) {
            $attributes .= ' class="' . View::e($options['class']) . '"';
        }

        return '<form' . $attributes . '>'
            . '<input type="hidden" name="_token" value="' . View::e(Session::csrfToken()) . '">';
    }

    public static function close(): string
    {
        return '</form>';
    }

    public static function hidden(string $name, string|int|null $value): string
    {
        return '<input type="hidden" name="' . View::e($name) . '" value="' . View::e((string) $value) . '">';
    }

    /**
     * $options: type, value, hint, placeholder, required, attrs, wrapper_class, unit
     */
    public static function input(string $name, string $label, array $errors, array $old, array $options = []): string
    {
        $type  = $options['type'] ?? 'text';
        $value = $old[$name] ?? $options['value'] ?? '';
        $error = $errors[$name] ?? null;

        $attributes = ' type="' . View::e($type) . '"'
            . ' id="' . View::e($name) . '"'
            . ' name="' . View::e($name) . '"'
            . ' value="' . View::e((string) $value) . '"'
            . ' class="' . ($error === null ? '' : 'error') . '"';

        if (! empty($options['placeholder'])) {
            $attributes .= ' placeholder="' . View::e($options['placeholder']) . '"';
        }

        if (! empty($options['required'])) {
            $attributes .= ' required';
        }

        if (! empty($options['attrs'])) {
            $attributes .= ' ' . $options['attrs'];
        }

        return '<div class="field ' . View::e($options['wrapper_class'] ?? '') . '">'
            . '<label for="' . View::e($name) . '">' . View::e($label) . '</label>'
            . '<input' . $attributes . '>'
            . self::hint($options['hint'] ?? null, $options['hint_html'] ?? null)
            . self::error($error)
            . '</div>';
    }

    /**
     * $items: list of ['value' => …, 'label' => …, 'price' => …, 'unit' => …]
     */
    public static function select(string $name, string $label, array $items, array $errors, array $old, array $options = []): string
    {
        $selected = (string) ($old[$name] ?? $options['value'] ?? '');
        $error    = $errors[$name] ?? null;

        $attributes = ' id="' . View::e($name) . '"'
            . ' name="' . View::e($name) . '"'
            . ' class="' . ($error === null ? '' : 'error') . '"';

        if (! empty($options['fills_price'])) {
            $attributes .= ' data-fills-price="' . View::e($options['fills_price']) . '"';
        }

        if (! empty($options['required'])) {
            $attributes .= ' required';
        }

        if (! empty($options['attrs'])) {
            $attributes .= ' ' . $options['attrs'];
        }

        $html = '<select' . $attributes . '>';

        if (isset($options['placeholder'])) {
            $html .= '<option value="">' . View::e($options['placeholder']) . '</option>';
        }

        foreach ($items as $item) {
            $value = (string) $item['value'];

            $html .= '<option value="' . View::e($value) . '"'
                . (isset($item['price']) ? ' data-price="' . View::e(Money::format((int) $item['price'])) . '"' : '')
                . (isset($item['unit']) ? ' data-unit="' . View::e((string) $item['unit']) . '"' : '')
                . ($value === $selected ? ' selected' : '')
                . '>' . View::e($item['label']) . '</option>';
        }

        $html .= '</select>';

        return '<div class="field ' . View::e($options['wrapper_class'] ?? '') . '">'
            . '<label for="' . View::e($name) . '">' . View::e($label) . '</label>'
            . $html
            . self::hint($options['hint'] ?? null, $options['hint_html'] ?? null)
            . self::error($error)
            . '</div>';
    }

    public static function textarea(string $name, string $label, array $errors, array $old, array $options = []): string
    {
        $value = $old[$name] ?? $options['value'] ?? '';
        $error = $errors[$name] ?? null;

        return '<div class="field ' . View::e($options['wrapper_class'] ?? '') . '">'
            . '<label for="' . View::e($name) . '">' . View::e($label) . '</label>'
            . '<textarea id="' . View::e($name) . '" name="' . View::e($name) . '" rows="2"'
            . ' class="' . ($error === null ? '' : 'error') . '">' . View::e((string) $value) . '</textarea>'
            . self::hint($options['hint'] ?? null, $options['hint_html'] ?? null)
            . self::error($error)
            . '</div>';
    }

    public static function checkbox(string $name, string $label, bool $checked): string
    {
        return '<div class="field">'
            . '<label for="' . View::e($name) . '">'
            . '<input type="checkbox" id="' . View::e($name) . '" name="' . View::e($name) . '" value="1"'
            . ($checked ? ' checked' : '') . ' style="width:auto;margin-left:8px">'
            . View::e($label)
            . '</label>'
            . '</div>';
    }

    /** The live-calculated total box shown under quantity × price forms. */
    public static function totalBox(string $caption = 'الإجمالي'): string
    {
        return '<div class="field">'
            . '<label>&nbsp;</label>'
            . '<div class="total-box"><small>' . View::e($caption) . '</small><span data-total>₪ 0.00</span></div>'
            . '</div>';
    }

    public static function submit(string $label): string
    {
        return '<button class="btn" type="submit">' . View::e($label) . '</button>';
    }

    private static function hint(?string $hint, ?string $rawHint = null): string
    {
        if ($rawHint !== null) {
            return '<div class="hint">' . $rawHint . '</div>';
        }

        return $hint === null ? '' : '<div class="hint">' . View::e($hint) . '</div>';
    }

    private static function error(?string $error): string
    {
        return $error === null ? '' : '<div class="msg">' . View::e($error) . '</div>';
    }
}
