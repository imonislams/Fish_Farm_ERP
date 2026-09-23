<?php

/*
|--------------------------------------------------------------------------
| Fish Farm ERP — Application Settings
|--------------------------------------------------------------------------
| Small, non-secret application configuration that the ERP code reads.
| Business data (company details, settings table rows) belongs in the database.
| The values below are SEED DEFAULTS used only by AdminUserSeeder when the
| `companies` row is first created. After that, the Settings module is the
| source of truth.
*/

return [

    // Reported in the footer and used for cache-busting notes in the docs.
    'version' => 'v0.1.0-architecture',

    /*
    |--------------------------------------------------------------------------
    | Company (Version 1 — single company)
    |--------------------------------------------------------------------------
    | Seed defaults for the ONE `companies` row. There is no company selector and
    | no multi-company support in Version 1 — see docs/DATABASE.md §1.
    | Edit these before seeding, or change the record later in Farm Settings.
    */
    'company' => [
        'name' => env('COMPANY_NAME', 'Fish Farm'),
        'code' => env('COMPANY_CODE', 'FISHFARM'),
        'phone' => env('COMPANY_PHONE'),
        'email' => env('COMPANY_EMAIL'),
        'address' => env('COMPANY_ADDRESS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | First system administrator
    |--------------------------------------------------------------------------
    | Used once by AdminUserSeeder to create the initial Super Admin. No password
    | is hard-coded: set ADMIN_PASSWORD in .env, otherwise a random one is
    | generated and printed by the seeder.
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'System Administrator'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    | Presentation-only defaults. The authoritative value is the `companies`
    | row; per-farm overrides live in the settings table once that module lands.
    */
    'currency_code' => 'BDT',
    // NOTE: double-quoted on purpose — PHP only expands \u{...} escapes inside
    // double quotes. A single-quoted '\u{09F3}' is the literal 8-character text
    // "\u{09F3}", which is what leaked into the UI as "\u{09F3}12,600.00".
    'currency_symbol' => "\u{09F3}", // ৳
    'currency_decimals' => 2,

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    | Default page sizes. Reports use a larger default than plain lists.
    */
    'pagination' => [
        'default' => 15,
        'reports' => 25,
        'max' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Localisation
    |--------------------------------------------------------------------------
    | The UI is English-first but must render Bengali (Bangla) text safely.
    */
    'supported_locales' => ['en', 'bn'],

    /*
    |--------------------------------------------------------------------------
    | PWA
    |--------------------------------------------------------------------------
    | Theme colours shared with public/manifest.webmanifest.
    | Keep these in sync with the CSS tokens in resources/css/app.css.
    */
    'pwa' => [
        'name' => 'Fish Farm ERP',
        'short_name' => 'FishFarm',
        'theme_color' => '#14532d',
        'background_color' => '#f1f5f4',
        'display' => 'standalone',
        'start_url' => '/dashboard',
        // Asset caching only — authenticated responses are never cached. See docs/PWA.md.
        'cache_version' => 'fishfarm-v1',
    ],
];
