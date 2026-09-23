<?php

/*
|--------------------------------------------------------------------------
| Pond Management — Value catalogue
|--------------------------------------------------------------------------
| Single source of truth for the pond domain's enumerations:
|
|   - pond statuses      (Active / Inactive / Maintenance / Empty)
|   - size units         (Decimal / Acre / Hectare / Square metre / Square foot)
|   - depth units        (Metre / Foot)
|
| RATIONALE (required by the Phase 2 brief): status strings must never be
| scattered through controllers and Blade templates. Every status badge, filter
| dropdown and validation rule reads from here, so a typo cannot produce a pond
| in a state the UI does not know how to render.
|
| The database stores the KEY (`active`, `maintenance`, …). The LABEL is for
| display and may be re-worded freely without a data migration.

| Consumed by:
|   - App\Models\Pond::statusLabel() / scopeStatus() / STATUSES
|   - App\Http\Requests\Pond\{Store,Update}PondRequest  (Rule::in)
|   - App\Services\Pond\PondService                     (status → is_active)
|   - resources/views/ponds/*                           (badges, filters, counts)
|
| See docs/BUSINESS_LOGIC.md §10 and docs/DATABASE.md §5.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Pond statuses
    |--------------------------------------------------------------------------
    | `active`      the pond is stocked or ready to be stocked — the normal state
    | `inactive`    the pond is not in use this season, but not being worked on
    | `maintenance` the pond is being repaired/prepared and must not be stocked
    | `empty`       the pond exists and is in good order, currently holding no stock
    |
    | `usable` marks the statuses a pond may be stocked from. It is a display/
    | business hint only — no stocking module exists yet.
    |
    | `is_active` (the boolean column) is derived from the status by PondService:
    | `active` and `empty` are usable, the others are not. The two can therefore
    | never contradict each other.
    */
    'statuses' => [
        'active' => [
            'label' => 'Active',
            'tone' => 'success',
            'usable' => true,
            'description' => 'In use, stocked or ready for stocking.',
        ],
        'inactive' => [
            'label' => 'Inactive',
            'tone' => 'danger',
            'usable' => false,
            'description' => 'Not in use. Retained for reference.',
        ],
        'maintenance' => [
            'label' => 'Maintenance',
            'tone' => 'warning',
            'usable' => false,
            'description' => 'Under repair or preparation. Cannot be stocked.',
        ],
        'empty' => [
            'label' => 'Empty',
            'tone' => 'info',
            'usable' => true,
            'description' => 'In good order but currently holding no stock.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Size units
    |--------------------------------------------------------------------------
    | Stored verbatim on `ponds.size_unit`. `label` is the display form; `symbol`
    | is the short suffix used in tables.
    */
    'size_units' => [
        'decimal' => ['label' => 'Decimal (shotangsho)', 'symbol' => 'decimal'],
        'acre' => ['label' => 'Acre', 'symbol' => 'acre'],
        'hectare' => ['label' => 'Hectare', 'symbol' => 'ha'],
        'sqm' => ['label' => 'Square metre', 'symbol' => 'm²'],
        'sqft' => ['label' => 'Square foot', 'symbol' => 'ft²'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Depth units
    |--------------------------------------------------------------------------
    */
    'depth_units' => [
        'metre' => ['label' => 'Metre', 'symbol' => 'm'],
        'foot' => ['label' => 'Foot', 'symbol' => 'ft'],
    ],
];
