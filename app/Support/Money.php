<?php

namespace App\Support;

/**
 * Formats a monetary amount for display.
 *
 * Currency symbol/code comes from config (farm settings later), so changing the
 * currency is a configuration change, not a find-and-replace across views.
 */
final class Money
{
    public static function format(float|int|string|null $amount, bool $withSymbol = true): string
    {
        if ($amount === null) {
            return '—';
        }

        $value = (float) $amount;
        // Mirror the React `money()` formatter EXACTLY: a negative sign sits
        // between the symbol and the digits, and there is no space after the
        // symbol (e.g. ৳-19,155.00). Keeps PHP and JS output identical.
        $formatted = ($value < 0 ? '-' : '') . number_format(abs($value), 2);

        return $withSymbol ? Currency::symbol() . $formatted : $formatted;
    }
}
