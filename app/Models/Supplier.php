<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Supplier — a vendor of feed, fingerlings and supplies.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * The outstanding due is derived by Finance\SupplierBalanceService, not stored.
 */
class Supplier extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'opening_balance',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

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

    /** A supplier with any purchase or payment history must not be deleted. */
    public function isDeletable(): bool
    {
        // Prefer eager-loaded counts (index pages use withCount); fall back to a
        // live query when the counts were not pre-loaded.
        $purchases = $this->purchases_count ?? $this->purchases()->count();
        $payments = $this->payments_count ?? $this->payments()->count();

        return $purchases === 0 && $payments === 0;
    }

    /** Display label — the supplier's name is the whole label. */
    public function displayName(): string
    {
        return $this->name;
    }
}
