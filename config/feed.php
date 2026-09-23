<?php

/*
|--------------------------------------------------------------------------
| Feed (Food) Management — Value catalogue
|--------------------------------------------------------------------------
| Single source of truth for the feed domain's enumerations:
|
|   - adjustment reasons  (why a manual stock correction was made)
|   - adjustment directions (in / out, with display labels)
|
| RATIONALE: like config/ponds.php and config/fish.php, reason/direction strings
| must never be scattered through controllers and Blade templates. Every dropdown
| and validation rule reads from here, so a typo cannot produce a record the UI
| cannot render.
|
| Consumed by:
|   - App\Http\Requests\Feed\*Request       (Rule::in)
|   - App\Services\Feed\FeedStockService
|   - resources/views/feed/*                (dropdowns, badges)
|
| See docs/BUSINESS_LOGIC.md §2 and docs/DATABASE.md §5.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Adjustment directions
    |--------------------------------------------------------------------------
    | Direction keys are stored on `feed_stock_adjustments.direction`. `tone` is
    | the badge colour used in the UI.
    */
    'adjustment_directions' => [
        'in' => ['label' => 'Increase stock', 'tone' => 'success'],
        'out' => ['label' => 'Decrease stock', 'tone' => 'warning'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Adjustment reasons
    |--------------------------------------------------------------------------
    | WHY a manual correction was made. A reason is required — stock is never
    | changed silently (docs/BUSINESS_LOGIC.md §2).
    */
    'adjustment_reasons' => [
        'count_correction' => ['label' => 'Physical count correction'],
        'spoilage' => ['label' => 'Spoilage / damaged'],
        'wastage' => ['label' => 'Wastage'],
        'expiry' => ['label' => 'Expired'],
        'return' => ['label' => 'Returned to supplier'],
        'opening_balance' => ['label' => 'Opening balance'],
        'data_entry_error' => ['label' => 'Data-entry error'],
        'other' => ['label' => 'Other'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock unit
    |--------------------------------------------------------------------------
    | Feed stock is ALWAYS kilograms internally, to 3 decimals. The catalogue
    | stores a display unit per feed type (e.g. "kg bag"), but every quantity
    | written to the stock tables is kg so totals and FCR agree.
    */
    'stock_unit' => 'kg',
    'kg_decimals' => 3,

    /*
    |--------------------------------------------------------------------------
    | Low stock
    |--------------------------------------------------------------------------
    | Whether the low-stock signal is surfaced in the UI. The threshold itself
    | lives on each feed type (`low_stock_level_kg`).
    */
    'low_stock_badge' => true,
];
