<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupplierPayment — money paid to a supplier (reduces the due).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 */
class SupplierPayment extends Model
{
    protected $fillable = [
        'supplier_id',
        'purchase_id',
        'amount',
        'method',
        'paid_on',
        'reference',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSupplier(Builder $query, int|string $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function methodLabel(): string
    {
        return config("finance.payment_methods.{$this->method}.label", $this->method);
    }
}
