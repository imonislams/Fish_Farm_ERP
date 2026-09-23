<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ExpenseCategory — classification of expenses (editable master data).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 */
class ExpenseCategory extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ExpenseEntry::class);
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

        return $query->where('name', 'like', '%' . $escaped . '%');
    }

    /** A category in use by any expense must not be deleted. */
    public function isDeletable(): bool
    {
        // Prefer the eager-loaded count (index pages use withCount); fall back to
        // a live query when the count was not pre-loaded.
        $expenses = $this->expenses_count ?? $this->expenses()->count();

        return $expenses === 0;
    }
}
