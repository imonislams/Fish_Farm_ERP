<?php

namespace App\Support;

/**
 * Standard date/time formatting helpers.
 *
 * Keeping formatting in one place guarantees a consistent presentation across
 * every module and report. Business date math belongs in services, not here.
 */
final class Dates
{
    public const DISPLAY = 'd M Y';
    public const DISPLAY_LONG = 'd M Y, h:i A';
    public const INPUT = 'Y-m-d';

    public static function display(mixed $date): string
    {
        return $date ? \Illuminate\Support\Carbon::parse($date)->format(self::DISPLAY) : '—';
    }

    public static function displayLong(mixed $date): string
    {
        return $date ? \Illuminate\Support\Carbon::parse($date)->format(self::DISPLAY_LONG) : '—';
    }

    public static function input(mixed $date): string
    {
        return $date ? \Illuminate\Support\Carbon::parse($date)->format(self::INPUT) : '';
    }
}
