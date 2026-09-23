<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FeedType — the feed product catalogue.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * Stock is NOT a column here — it is always derived from the movement records
 * (purchases in, usages out, adjustments ±) by FeedStockService. That way the
 * stock figure can never drift from the movements that produced it.
 *
 * Deleting a feed type still referenced by a purchase, usage or adjustment is
 * refused by the database FK (`restrictOnDelete`) and turned into a clear
 * application error by App\Services\Feed\FeedTypeService.
 */
class FeedType extends Model
{
    protected $fillable = [
        'name',
        'brand',
        'protein_percent',
        'unit',
        'package_weight_kg',
        'default_unit_cost',
        'low_stock_level_kg',
        'critical_stock_level_kg',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'protein_percent' => 'decimal:2',
            'package_weight_kg' => 'decimal:3',
            'default_unit_cost' => 'decimal:2',
            'low_stock_level_kg' => 'decimal:3',
            'critical_stock_level_kg' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    /** Feed bought (stock IN). */
    public function purchases(): HasMany
    {
        return $this->hasMany(FeedPurchase::class);
    }

    /** Feed given to ponds (stock OUT). */
    public function usages(): HasMany
    {
        return $this->hasMany(FeedUsage::class);
    }

    /** Manual stock corrections. */
    public function adjustments(): HasMany
    {
        return $this->hasMany(FeedStockAdjustment::class);
    }

    /* ---------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------*/

    /** Only types that may be selected for a new movement. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Free-text search across the fields a user searches by. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        // Escape LIKE wildcards so "%" or "_" match a literal character rather
        // than everything. Backslash first, or it would double-escape the rest.
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $like = '%' . $escaped . '%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'like', $like)
                ->orWhere('brand', 'like', $like);
        });
    }

    /* ---------------------------------------------------------------------
     | Behaviour / presentation
    |---------------------------------------------------------------------*/

    /**
     * May this feed type be deleted?
     *
     * A type with any recorded movement must not disappear from under its stock
     * history. This is the single check the UI and the service share — the
     * service still enforces it, because it is the write path.
     */
    public function isDeletable(): bool
    {
        // Prefer eager-loaded counts (index pages use withCount); fall back to a
        // live query when the counts were not pre-loaded.
        $purchases = $this->purchases_count ?? $this->purchases()->count();
        $usages = $this->usages_count ?? $this->usages()->count();
        $adjustments = $this->adjustments_count ?? $this->adjustments()->count();

        return $purchases === 0 && $usages === 0 && $adjustments === 0;
    }

    /** Display-ready label, e.g. "Sinking Pellet (Quality Feed)". */
    public function displayName(): string
    {
        return $this->brand ? "{$this->name} ({$this->brand})" : $this->name;
    }
}
