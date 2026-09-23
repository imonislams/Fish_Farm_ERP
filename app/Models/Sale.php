<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sale — a fish sale to a customer.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * DERIVED COLUMNS (set only by SalesService, never from input):
 *   total      = subtotal − discount
 *   due_amount = total − paid_amount
 *   status     = paid | partial | due   (from `total` vs `paid_amount`)
 */
class Sale extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_DUE = 'due';

    protected $fillable = [
        'customer_id',
        'invoice_no',
        'sale_date',
        'subtotal',
        'discount',
        'total',
        'paid_amount',
        'due_amount',
        'status',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------------------------------------------------------------
     | Scopes / presentation
    |---------------------------------------------------------------------*/

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /** Human label for the settlement status. */
    public function statusLabel(): string
    {
        return config("finance.settlement_statuses.{$this->status}.label", $this->status);
    }

    /** Badge tone for the settlement status. */
    public function statusTone(): string
    {
        return config("finance.settlement_statuses.{$this->status}.tone", 'default');
    }

    /** True when the sale is fully settled. */
    public function isSettled(): bool
    {
        return (float) $this->due_amount <= 0.0;
    }
}
