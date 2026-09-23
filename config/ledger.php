<?php

/*
|--------------------------------------------------------------------------
| Pond Ledger — Value catalogue
|--------------------------------------------------------------------------
| Single source of truth for the pond ledger's enumerations:
|
|   - entry types   (debit / credit, with display labels and badge tones)
|   - categories    (what the money was for), split by type
|   - source types  (which transaction produced an entry)
|
| RATIONALE: like config/ponds.php, config/fish.php and config/feed.php, the
| type/category strings must never be scattered through controllers and Blade
| templates. Every dropdown and validation rule reads from here, so a typo cannot
| produce an entry the UI cannot render.
|
| Consumed by:
|   - App\Http\Requests\Pond\StorePondLedgerEntryRequest   (Rule::in)
|   - App\Services\Pond\PondLedgerService
|   - resources/views/ledger/*                            (dropdowns, badges)
|
| See docs/BUSINESS_LOGIC.md §4 and docs/DATABASE.md §5.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Entry types — the sign convention
    |--------------------------------------------------------------------------
    | `debit`  money OUT of the farm, attributed to the pond (a cost)
    | `credit` money IN to the farm, attributed to the pond (revenue)
    |
    | Pond Profit = Σ credits − Σ debits  (applied by PondLedgerService via
    | LedgerRules; never recomputed in a view).
    */
    'entry_types' => [
        'debit' => ['label' => 'Expense (debit)', 'short' => 'Expense', 'tone' => 'danger'],
        'credit' => ['label' => 'Income (credit)', 'short' => 'Income', 'tone' => 'success'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction types — the Pond Ledger timeline
    |--------------------------------------------------------------------------
    | The Pond Ledger is primarily a COMPLETE pond transaction/history system:
    | every movement that touched a pond appears in one chronological timeline.
    | Each transaction type carries a display label and a badge tone so the
    | timeline is readable at a glance.
    |
    | Implemented today:
    |   stocking, mortality, transfer_in, transfer_out
    |
    | Reserved for the modules that will contribute rows later (no fake rows are
    | created now — the timeline simply has no rows of that type):
    |   feed, sale, inspection, harvest
    |
    | Adding a source type = adding a row producer in PondLedgerTimelineService;
    | the entry here is what the timeline badge uses to render it.
    */
    'transaction_types' => [
        'stocking' => ['label' => 'Stocking', 'short' => 'stocking', 'tone' => 'success', 'icon' => 'plus'],
        'mortality' => ['label' => 'Death (Mortality)', 'short' => 'mortality', 'tone' => 'danger', 'icon' => 'alert'],
        'transfer_in' => ['label' => 'Transfer in', 'short' => 'transfer in', 'tone' => 'info', 'icon' => 'fish'],
        'transfer_out' => ['label' => 'Transfer out', 'short' => 'transfer out', 'tone' => 'warning', 'icon' => 'truck'],
        'feed' => ['label' => 'Feed', 'short' => 'feed', 'tone' => 'primary', 'icon' => 'feed'],
        'sale' => ['label' => 'Sale', 'short' => 'sale', 'tone' => 'success', 'icon' => 'cart'],
        'inspection' => ['label' => 'Inspection', 'short' => 'inspection', 'tone' => 'default', 'icon' => 'search'],
        'harvest' => ['label' => 'Harvest', 'short' => 'harvest', 'tone' => 'warning', 'icon' => 'truck'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    | What the money was for. Kept split by type so the form offers sensible
    | options and validation can require a category that matches the type.
    |
    | These are the categories the farm can record TODAY, by hand. When the
    | owning modules land (feed usage, sales, expenses) their services will write
    | entries with the same keys.
    */
    'categories' => [
        'debit' => [
            'feed' => 'Feed cost',
            'fingerlings' => 'Fish / fingerlings',
            'labour' => 'Labour',
            'medicine' => 'Medicine / treatment',
            'electricity' => 'Electricity / pumping',
            'equipment' => 'Equipment',
            'rent' => 'Pond rent / lease',
            'maintenance' => 'Maintenance / repair',
            'transport' => 'Transport',
            'other_expense' => 'Other expense',
        ],
        'credit' => [
            'fish_sale' => 'Fish sale',
            'fingerling_sale' => 'Fingerling sale',
            'subsidy' => 'Subsidy / grant',
            'other_income' => 'Other income',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Source types
    |--------------------------------------------------------------------------
    | Which transaction produced the entry, for traceability. `manual` is a
    | hand-recorded entry with no owning module row. The rest are reserved for the
    | services that will write entries when their modules land — they are listed
    | so the vocabulary is agreed up front.
    */
    'source_types' => [
        'manual' => 'Manual entry',
        'feed_usage' => 'Feed usage',
        'fish_stocking' => 'Fish stocking',
        'fish_mortality' => 'Fish mortality',
        'pond_transfer' => 'Pond transfer',
        'harvest' => 'Harvest',
        'sale' => 'Sale',
        'customer_payment' => 'Customer payment',
        'purchase' => 'Purchase',
        'supplier_payment' => 'Supplier payment',
        'expense' => 'Expense entry',
        'income' => 'Income entry',
    ],
];
