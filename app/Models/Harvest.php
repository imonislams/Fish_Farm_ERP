<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Harvest — fish removed from a pond (stock OUT).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A harvest can never drive a pond's stock below zero — FishStockService rejects
 * it before the row is written (docs/BUSINESS_LOGIC.md §2). The link to a sale
 * belongs to the Sales module and is not created here.
 */
class Harvest extends Model
{
    protected $fillable = [
        'pond_id',
        'fish_batch_id',
        'fish_species_id',
        'quantity',
        'total_weight_kg',
        'avg_weight_g',
        'harvested_on',
        'destination',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'total_weight_kg' => 'decimal:3',
            'avg_weight_g' => 'decimal:2',
            'harvested_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /** The stocking cycle this harvest belongs to (optional). */
    public function fishBatch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'fish_species_id');
    }

    /** The user who recorded the harvest. */
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

    /** Display-ready weight, e.g. "1200 kg" — an em dash when not recorded. */
    public function weightDisplay(): string
    {
        if ($this->total_weight_kg === null || $this->total_weight_kg === '') {
            return '—';
        }

        $number = rtrim(rtrim(number_format((float) $this->total_weight_kg, 3, '.', ''), '0'), '.');

        return "{$number} kg";
    }
}
