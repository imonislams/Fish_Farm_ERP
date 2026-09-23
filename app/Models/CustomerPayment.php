<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerPayment — money received from a customer (reduces the due).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 */
class CustomerPayment extends Model
{
    protected $fillable = [
        'customer_id',
        'sale_id',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForCustomer(Builder $query, int|string $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /** Human label for the payment method. */
    public function methodLabel(): string
    {
        return config("finance.payment_methods.{$this->method}.label", $this->method);
    }
}
