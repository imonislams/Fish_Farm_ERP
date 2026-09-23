<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FishSpecies — catalogue of species the farm raises.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * Deleting a species still referenced by a stocking or harvest is refused by the
 * database FK (`restrictOnDelete`) and turned into a clear application error by
 * App\Services\Fish\FishSpeciesService.
 */
class FishSpecies extends Model
{
    protected $fillable = [
        'name',
        'local_name',
        'scientific_name',
        'default_price_per_kg',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'default_price_per_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    /** Stocking records that put this species into a pond. */
    public function stockings(): HasMany
    {
        return $this->hasMany(FishStocking::class);
    }

    /** Harvest records that removed this species from a pond. */
    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    /* ---------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------*/

    /** Only species that may be selected for a new stocking/harvest. */
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
                ->orWhere('local_name', 'like', $like)
                ->orWhere('scientific_name', 'like', $like);
        });
    }
}
