<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FishStocking — fish put INTO a pond (stock IN).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * The derived weight (`total_weight_kg`) is computed by FishStockService from the
 * quantity and average weight, so it is never entered twice and never drifts.
 */
class FishStocking extends Model
{
    protected $fillable = [
        'pond_id',
        'fish_batch_id',
        'fish_species_id',
        'quantity',
        'avg_weight_g',
        'total_weight_kg',
        'unit_cost',
        'total_cost',
        'stocked_on',
        'reference',
        'supplier_name',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'avg_weight_g' => 'decimal:2',
            'total_weight_kg' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'stocked_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /** The stocking cycle this movement belongs to (optional). */
    public function fishBatch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'fish_species_id');
    }

    /** The user who recorded the stocking. */
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

    /** Display-ready weight, e.g. "125.5 kg" — an em dash when not recorded. */
    public function weightDisplay(): string
    {
        return $this->measurementDisplay($this->total_weight_kg, 'kg');
    }

    /** Display-ready average weight, e.g. "250 g" — an em dash when not recorded. */
    public function avgWeightDisplay(): string
    {
        return $this->measurementDisplay($this->avg_weight_g, 'g');
    }

    /** Trim trailing zeros and append the unit; null renders as an em dash. */
    private function measurementDisplay(mixed $value, string $unit): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $number = rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');

        return "{$number} {$unit}";
    }
}
