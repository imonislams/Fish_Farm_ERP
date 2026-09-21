<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

/**
 * Provides the SINGLE company to views.
 *
 * Version 1 is single-company (docs/DATABASE.md §1), so "the company" is a
 * singular lookup. This class is the ONE place that resolves it, so:
 *   - views never query the database directly
 *   - branding (name / logo) is never hard-coded
 *   - a future multi-company version has a single seam to change
 *
 * The value is cached for the request lifetime to avoid repeated queries; the
 * Company model forgets the cache when it is saved.
 */
final class CompanyContext
{
    private const CACHE_KEY = 'fishfarm.company';

    /** The company, or null before the system has been seeded. */
    public static function get(): ?Company
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(30), function (): ?Company {
            try {
                return Company::query()->orderBy('id')->first();
            } catch (\Throwable) {
                // Table may not exist yet (pre-migration). Never break the UI.
                return null;
            }
        });
    }

    /** Company name, falling back to the configured app name. */
    public static function name(): string
    {
        return self::get()?->name ?? config('app.name');
    }

    /** Public URL of the logo, or null when none is set. */
    public static function logoUrl(): ?string
    {
        $logo = self::get()?->logo;

        return $logo ? asset('storage/' . $logo) : null;
    }

    /**
     * 1–2 letter initials for the brand mark, derived from the company name.
     * Used when no logo is uploaded so the UI never shows a placeholder image.
     */
    public static function initials(): string
    {
        $name = trim(self::name());
        $words = preg_split('/\s+/', $name) ?: [];
        $words = array_filter($words);

        if (count($words) >= 2) {
            return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }

        return strtoupper(mb_substr($name, 0, 2));
    }

    /** Invalidate the cached company (called when company settings are saved). */
    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
