<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Customer — a buyer of fish.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * The outstanding balance is NOT a column: it is derived by
 * Finance\CustomerBalanceService from sales and payments (docs/BUSINESS_LOGIC.md §3).
 * `opening_balance` IS stored — it is money owed before the system existed.
 */
class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'opening_balance',
        'credit_limit',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /* ---------------------------------------------------------------------
     | Scopes
    |---------------------------------------------------------------------*/

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $like = '%' . $escaped . '%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }

    /* ---------------------------------------------------------------------
     | Presentation
    |---------------------------------------------------------------------*/

    /** A customer with any sale or payment history must not be deleted. */
    public function isDeletable(): bool
    {
        // Prefer eager-loaded counts (index pages call withCount to avoid N+1);
        // fall back to a live query when the counts were not pre-loaded (edit page).
        $sales = $this->sales_count ?? $this->sales()->count();
        $payments = $this->payments_count ?? $this->payments()->count();

        return $sales === 0 && $payments === 0;
    }
}
