<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Purchase — a purchase from a supplier (header).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * DERIVED COLUMNS (set only by PurchaseService):
 *   total = subtotal − discount;  due_amount = total − paid_amount;  status.
 */
class Purchase extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_DUE = 'due';

    protected $fillable = [
        'supplier_id',
        'invoice_no',
        'purchase_date',
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
            'purchase_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function statusLabel(): string
    {
        return config("finance.settlement_statuses.{$this->status}.label", $this->status);
    }

    public function statusTone(): string
    {
        return config("finance.settlement_statuses.{$this->status}.tone", 'default');
    }

    public function isSettled(): bool
    {
        return (float) $this->due_amount <= 0.0;
    }
}
