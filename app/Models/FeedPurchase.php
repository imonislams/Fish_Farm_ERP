<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeedPurchase — feed bought from a supplier (feed stock IN).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `total_cost` is DERIVED by FeedStockService (quantity_kg × unit_cost) so the
 * stored figure never drifts from its inputs.
 */
class FeedPurchase extends Model
{
    protected $fillable = [
        'feed_type_id',
        'quantity_kg',
        'unit_cost',
        'total_cost',
        'purchased_on',
        'invoice_no',
        'supplier_name',
        'paid_amount',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'purchased_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function feedType(): BelongsTo
    {
        return $this->belongsTo(FeedType::class);
    }

    /** The user who recorded the purchase. */
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

    /** Display-ready quantity, e.g. "250 kg". */
    public function quantityDisplay(): string
    {
        return $this->measurementDisplay($this->quantity_kg, 'kg');
    }

    /** Outstanding amount on this purchase (total − paid). Null when no cost. */
    public function dueAmount(): ?string
    {
        if ($this->total_cost === null) {
            return null;
        }

        $due = (float) $this->total_cost - (float) ($this->paid_amount ?? 0);

        return number_format(max(0, $due), 2, '.', '');
    }

    private function measurementDisplay(mixed $value, string $unit): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $number = rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');

        return "{$number} {$unit}";
    }
}
