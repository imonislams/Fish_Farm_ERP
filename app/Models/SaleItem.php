<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SaleItem — one line of a sale.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `line_total` is DERIVED by SalesService (weight × price, or count × price).
 */
class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'fish_species_id',
        'pond_id',
        'quantity',
        'weight_kg',
        'unit_price',
        'line_total',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'weight_kg' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'fish_species_id');
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /** Display label, e.g. "Rohu" or the free-text description. */
    public function label(): string
    {
        return $this->species?->name ?? $this->description ?? 'Item';
    }
}
