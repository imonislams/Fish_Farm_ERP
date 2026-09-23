<?php

/*
|--------------------------------------------------------------------------
| Fish Stock — Value catalogue
|--------------------------------------------------------------------------
| Single source of truth for the fish-stock domain's enumerations:
|
|   - mortality causes   (the reason codes offered when recording a death)
|   - weight units       (display only; quantities are counts, weights are grams/kg)
|
| RATIONALE: like config/ponds.php, status/reason strings must never be scattered
| through controllers and Blade templates. Every dropdown and validation rule
| reads from here, so a typo cannot produce a record the UI cannot render.
|
| Consumed by:
|   - App\Http\Requests\Fish\*Request                (Rule::in)
|   - App\Services\Fish\FishStockService
|   - resources/views/fish/*                        (dropdowns, badges)
|
| See docs/BUSINESS_LOGIC.md §2 and docs/DATABASE.md §5.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Mortality causes
    |--------------------------------------------------------------------------
    | The reason codes offered when recording a mortality. A cause is optional —
    | sometimes fish die for an unknown reason and forcing a value would create
    | fake precision. `label` is for display; the KEY is stored.
    */
    'mortality_causes' => [
        'disease' => ['label' => 'Disease'],
        'water_quality' => ['label' => 'Poor water quality'],
        'oxygen' => ['label' => 'Low oxygen'],
        'handling' => ['label' => 'Handling / transport stress'],
        'predator' => ['label' => 'Predator'],
        'unknown' => ['label' => 'Unknown'],
        'other' => ['label' => 'Other'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Quantity units
    |--------------------------------------------------------------------------
    | Fish quantities are always whole fish (a count). This is documented here
    | so the UI can label columns consistently and so future modules (sales,
    | FCR) agree on the meaning of "quantity".
    */
    'quantity_unit' => 'fish',

    /*
    |--------------------------------------------------------------------------
    | Weight precision
    |--------------------------------------------------------------------------
    | Weights are stored in kg with 3 decimals and grams with 2, matching the
    | column definitions in docs/DATABASE.md §8.
    */
    'grams_decimals' => 2,
    'kg_decimals' => 3,
];
