<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeedStockAdjustment — manual correction of feed stock, with a reason.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * Stock is never changed silently: a direction, a reason key and a date are
 * always recorded, so any difference between the book figure and a physical
 * count is explainable (docs/BUSINESS_LOGIC.md §2).
 */
class FeedStockAdjustment extends Model
{
    /**
     * Direction keys. Canonical values and labels live in config/feed.php —
     * never hard-coded as string literals in controllers or views.
     */
    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    protected $fillable = [
        'feed_type_id',
        'direction',
        'quantity_kg',
        'reason',
        'note',
        'adjusted_on',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:3',
            'adjusted_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function feedType(): BelongsTo
    {
        return $this->belongsTo(FeedType::class);
    }

    /** The user who recorded the adjustment. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------*/

    public function scopeOfType(Builder $query, int|string $typeId): Builder
    {
        return $query->where('feed_type_id', $typeId);
    }

    /* ---------------------------------------------------------------------
     | Presentation helpers
    |---------------------------------------------------------------------*/

    /** True when this adjustment increases stock. */
    public function isIncrease(): bool
    {
        return $this->direction === self::DIRECTION_IN;
    }

    /** The signed quantity, e.g. "+5 kg" or "−2.5 kg". */
    public function signedQuantityDisplay(): string
    {
        $number = rtrim(rtrim(number_format((float) $this->quantity_kg, 3, '.', ''), '0'), '.');

        return $this->isIncrease() ? "+{$number} kg" : "−{$number} kg";
    }

    /** Human label for the direction from the catalogue. */
    public function directionLabel(): string
    {
        return config("feed.adjustment_directions.{$this->direction}.label", $this->direction);
    }

    /** Badge tone for the direction. */
    public function directionTone(): string
    {
        return config("feed.adjustment_directions.{$this->direction}.tone", 'default');
    }

    /**
     * Human label for the reason from the catalogue, or the stored value when it
     * is not a known key (a data defect, not a display choice).
     */
    public function reasonLabel(): string
    {
        if ($this->reason === null || $this->reason === '') {
            return '—';
        }

        return config("feed.adjustment_reasons.{$this->reason}.label", $this->reason);
    }
}
