<?php

/*
|--------------------------------------------------------------------------
| FCR & Growth — Value catalogue
|--------------------------------------------------------------------------
| Single source of truth for the FCR & Growth domain's enumerations:
|
|   - pond health statuses   (the outcome recorded on an inspection)
|   - inspection parameters  (the water-quality fields an inspection records)
|   - schedule frequencies    (how often a pond should be inspected)
|
| RATIONALE: like config/ponds.php, config/fish.php, config/feed.php and
| config/ledger.php, these strings must never be scattered through controllers and
| Blade templates. Every dropdown and validation rule reads from here, so a typo
| cannot produce a record the UI cannot render.
|
| Consumed by:
|   - App\Http\Requests\Fcr\*Request        (Rule::in)
|   - App\Services\Fcr\*Service
|   - resources/views/fcr/*                (dropdowns, badges)
|
| See docs/BUSINESS_LOGIC.md §1 and docs/DATABASE.md §5.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Pond health statuses
    |--------------------------------------------------------------------------
    | The outcome of an inspection. `tone` is the badge colour used in the UI.
    | `concern` marks the statuses that should be visible at a glance.
    */
    'health_statuses' => [
        'healthy' => ['label' => 'Healthy', 'tone' => 'success', 'concern' => false],
        'monitor' => ['label' => 'Monitor', 'tone' => 'info', 'concern' => false],
        'warning' => ['label' => 'Warning', 'tone' => 'warning', 'concern' => true],
        'critical' => ['label' => 'Critical', 'tone' => 'danger', 'concern' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inspection parameters
    |--------------------------------------------------------------------------
    | The water-quality readings an inspection may record. Each entry describes
    | how the value is stored and displayed, so validation and the UI agree.
    |
    | `min`/`max` are GUARD RAILS for physically implausible input, not targets.
    | They are deliberately wide: this system records what was measured, it does
    | not decide what a "good" reading is.
    */
    'parameters' => [
        'water_ph' => ['label' => 'Water pH', 'decimals' => 2, 'min' => 0, 'max' => 14, 'suffix' => ''],
        'water_temp_c' => ['label' => 'Water temperature (°C)', 'decimals' => 2, 'min' => 0, 'max' => 60, 'suffix' => '°C'],
        'dissolved_oxygen' => ['label' => 'Dissolved oxygen (mg/L)', 'decimals' => 2, 'min' => 0, 'max' => 30, 'suffix' => 'mg/L'],
        'ammonia' => ['label' => 'Ammonia (mg/L)', 'decimals' => 3, 'min' => 0, 'max' => 50, 'suffix' => 'mg/L'],
        'turbidity' => ['label' => 'Turbidity (NTU)', 'decimals' => 2, 'min' => 0, 'max' => 1000, 'suffix' => 'NTU'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule frequencies
    |--------------------------------------------------------------------------
    | How often a pond should be inspected. `days` is the interval used to derive
    | `next_due_on` when a schedule is created or completed.
    */
    'frequencies' => [
        'daily' => ['label' => 'Daily', 'days' => 1],
        'every_2_days' => ['label' => 'Every 2 days', 'days' => 2],
        'weekly' => ['label' => 'Weekly', 'days' => 7],
        'fortnightly' => ['label' => 'Fortnightly', 'days' => 14],
        'monthly' => ['label' => 'Monthly', 'days' => 30],
        'quarterly' => ['label' => 'Quarterly', 'days' => 90],
    ],

    /*
    |--------------------------------------------------------------------------
    | Due-soon window
    |--------------------------------------------------------------------------
    | An inspection due within this many days is flagged as "due soon" rather
    | than simply "scheduled".
    */
    'due_soon_days' => 2,

    /*
    |--------------------------------------------------------------------------
    | FCR interpretation
    |--------------------------------------------------------------------------
    | Presentation bands only — they influence colour, never the calculation
    | (docs/BUSINESS_LOGIC.md §1). Mirrored by FcrResult::band().
    */
    'fcr_bands' => [
        'excellent' => ['label' => 'Excellent', 'tone' => 'success'],
        'good' => ['label' => 'Good', 'tone' => 'primary'],
        'fair' => ['label' => 'Fair', 'tone' => 'warning'],
        'poor' => ['label' => 'Poor', 'tone' => 'danger'],
        'unknown' => ['label' => 'Not available', 'tone' => 'default'],
    ],
];
