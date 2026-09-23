<?php

namespace App\Support;

/**
 * Currency — the ONE source of the symbol/format the UI uses.
 *
 * Version 1 is single-company: the company row (via config defaults) decides the
 * currency code and symbol. The React side mirrors this in `money()`; the actual
 * VALUE never travels as a formatted string — only the symbol/locale config does.
 */
final class Currency
{
    /** The 3-letter ISO code, e.g. "BDT". */
    public static function code(): string
    {
        return (string) (config('fishfarm.currency_code') ?? 'BDT');
    }

    /** The display symbol, e.g. "৳". */
    public static function symbol(): string
    {
        return (string) (config('fishfarm.currency_symbol') ?? '৳');
    }

    /** Decimal places (2 for all current currencies). */
    public static function decimals(): int
    {
        return (int) (config('fishfarm.currency_decimals') ?? 2);
    }

    /**
     * The shared props the header/dashboard/etc. need to format money client-side.
     *
     * @return array{code: string, symbol: string, decimals: int}
     */
    public static function shared(): array
    {
        return [
            'code' => self::code(),
            'symbol' => self::symbol(),
            'decimals' => self::decimals(),
        ];
    }
}
