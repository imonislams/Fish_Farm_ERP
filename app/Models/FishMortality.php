<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FishMortality — recorded fish deaths (stock OUT).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A mortality can never drive a pond's stock below zero — FishStockService
 * rejects it before the row is written (docs/BUSINESS_LOGIC.md §2).
 */
class FishMortality extends Model
{
    protected $fillable = [
        'pond_id',
        'fish_batch_id',
        'quantity',
        'avg_weight_g',
        'recorded_on',
        'reference',
        'cause',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'avg_weight_g' => 'decimal:2',
            'recorded_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /** The stocking cycle this mortality belongs to (optional). */
    public function fishBatch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class);
    }

    /** The user who recorded the mortality. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------*/

    public function scopeForPond(Builder $query, int|string $pondId): Builder
    {
        return $query->where('pond_id', $pondId);
    }

    /* ---------------------------------------------------------------------
     | Presentation helpers
    |---------------------------------------------------------------------*/

    /**
     * Human label for the cause from the catalogue, or the stored value itself
     * when it is not one of the known keys (a data defect, not a display choice).
     */
    public function causeLabel(): string
    {
        if ($this->cause === null || $this->cause === '') {
            return '—';
        }

        return config("fish.mortality_causes.{$this->cause}.label", $this->cause);
    }
}
