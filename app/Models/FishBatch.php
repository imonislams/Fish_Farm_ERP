<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FishBatch — a stocking cycle for one pond + species.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * SINGLE SOURCE OF TRUTH (docs/BUSINESS_LOGIC.md §2):
 *   The batch is a LABEL/grouping, NOT a second stock ledger. It stores no running
 *   quantity — `initial_quantity` is only the survival denominator. A batch's
 *   current quantity is always FishBatchService::currentQuantity(), computed from
 *   the movement rows tagged to it (stockings − mortalities − harvests). There is
 *   ONE authority for fish counts (FishStockService + the movement records).
 */
class FishBatch extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_HARVESTED = 'harvested';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'pond_id',
        'fish_species_id',
        'code',
        'started_on',
        'ended_on',
        'initial_quantity',
        'status',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
            'initial_quantity' => 'integer',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
    |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'fish_species_id');
    }

    /** Stockings that belong to this cycle. */
    public function stockings(): HasMany
    {
        return $this->hasMany(FishStocking::class);
    }

    /** Mortality records that belong to this cycle. */
    public function mortalities(): HasMany
    {
        return $this->hasMany(FishMortality::class);
    }

    /** Harvests that belong to this cycle. */
    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------------------------------------------------------------
     | Scopes
    |---------------------------------------------------------------------*/

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForPond(Builder $query, int|string $pondId): Builder
    {
        return $query->where('pond_id', $pondId);
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
            $q->where('code', 'like', $like)->orWhere('note', 'like', $like);
        });
    }

    /* ---------------------------------------------------------------------
     | Derived figures — always delegated, never stored
    |---------------------------------------------------------------------*/

    /** Live fish in this cycle (stocked − mortality − harvested). */
    public function currentQuantity(): int
    {
        return app(\App\Services\Fish\FishBatchService::class)->currentQuantity($this);
    }

    /** Survival rate (%) for this cycle, or null when nothing was put in. */
    public function survivalRate(): ?float
    {
        return app(\App\Services\Fish\FishBatchService::class)->survivalRate($this);
    }

    /* ---------------------------------------------------------------------
     | Presentation
    |---------------------------------------------------------------------*/

    public function statusDefinition(): ?array
    {
        return config("finance.batch_statuses.{$this->status}");
    }

    public function statusLabel(): string
    {
        return $this->statusDefinition()['label'] ?? ucfirst((string) $this->status);
    }

    public function statusTone(): string
    {
        return $this->statusDefinition()['tone'] ?? 'default';
    }

    /** True once the cycle has ended — the UI shows it read-only. */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
