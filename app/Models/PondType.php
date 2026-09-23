<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PondType — master data classifying ponds (grow-out, nursery, brood, …).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * Deleting a type that is still referenced by ponds is refused by the database
 * foreign key (`restrictOnDelete`) and turned into a clear application error by
 * App\Services\Pond\PondTypeService — never a silent delete or orphaned ponds.
 */
class PondType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    /** Ponds classified under this type. */
    public function ponds(): HasMany
    {
        return $this->hasMany(Pond::class);
    }

    /* ---------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------*/

    /** Only types that may be selected for a new pond. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /* ---------------------------------------------------------------------
     | Behaviour
     |---------------------------------------------------------------------*/

    /**
     * May this type be deleted?
     *
     * A type in use must not disappear from under its ponds. This is the single
     * check the UI and the service share — the service still enforces it, because
     * this is the write path (docs/PERMISSIONS.md §2).
     */
    public function isDeletable(): bool
    {
        // Prefer the eager-loaded count (index pages use withCount); fall back to
        // a live exists() when the count was not pre-loaded.
        return isset($this->ponds_count)
            ? $this->ponds_count === 0
            : ! $this->ponds()->exists();
    }
}
