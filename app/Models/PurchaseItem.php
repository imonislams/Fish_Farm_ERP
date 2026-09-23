<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PurchaseItem — one line of a purchase.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `line_total` is DERIVED by PurchaseService (quantity × unit_cost).
 */
class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'item_type',
        'feed_type_id',
        'fish_species_id',
        'description',
        'quantity',
        'unit_cost',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function feedType(): BelongsTo
    {
        return $this->belongsTo(FeedType::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'fish_species_id');
    }

    /** Human label for the item type. */
    public function typeLabel(): string
    {
        return config("finance.purchase_item_types.{$this->item_type}", $this->item_type);
    }

    /** Best available label for the line. */
    public function label(): string
    {
        return $this->feedType?->name
            ?? $this->species?->name
            ?? $this->description
            ?? $this->typeLabel();
    }
}
