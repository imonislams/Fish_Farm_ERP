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

        $formatted = number_format((float) $amount, 2);

        return $withSymbol
            ? trim(config('fishfarm.currency_symbol', '৳') . ' ' . $formatted)
            : $formatted;
    }
}
