<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Pond — the primary operating unit of the farm.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * MEASUREMENTS: `size` and `depth` are cast to `decimal:3` (strings) rather than
 * `float`, so exact values survive a round trip. They are summed and compared by
 * later modules (stocking density, feed per area, reports) where float drift
 * would show up. Formatting helpers return display-ready strings so no Blade file
 * has to do arithmetic (docs/ARCHITECTURE.md §3).
 *
 * FUTURE MODULES (fish stock, feed usage, growth, inspections, mortality,
 * harvest, pond ledger) will each add a `hasMany` here **when their table exists**.
 * They are deliberately NOT declared yet: declaring a relationship to a table that
 * does not exist would break at runtime (docs/DATABASE.md §4).
 */
class Pond extends Model
{
    /**
     * Canonical status keys. Stored in `ponds.status` and defined once in
     * config/ponds.php — never hard-coded as string literals in controllers or
     * views (Phase 2 brief §6).
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_EMPTY = 'empty';

    protected $fillable = [
        'pond_number',
        'name',
        'pond_type_id',
        'size',
        'size_unit',
        'depth',
        'depth_unit',
        'location',
        'water_source',
        'status',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'decimal:3',
            'depth' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    /** The pond's classification. */
    public function type(): BelongsTo
    {
        return $this->belongsTo(PondType::class, 'pond_type_id');
    }

    /** Fish put into this pond (Phase 3). */
    public function stockings(): HasMany
    {
        return $this->hasMany(FishStocking::class);
    }

    /** Fish deaths recorded for this pond (Phase 3). */
    public function mortalities(): HasMany
    {
        return $this->hasMany(FishMortality::class);
    }

    /** Fish removed from this pond (Phase 3). */
    public function harvests(): HasMany
    {
        return $this->hasMany(Harvest::class);
    }

    /** Feed given to this pond (Phase 4). */
    public function feedUsages(): HasMany
    {
        return $this->hasMany(FeedUsage::class);
    }

    /** Money in/out attributed to this pond (Phase 5). */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PondLedgerEntry::class);
    }

    /** Fish transferred out of this pond (Pond Ledger). */
    public function transfersOut(): HasMany
    {
        return $this->hasMany(PondTransfer::class, 'from_pond_id');
    }

    /** Fish transferred into this pond (Pond Ledger). */
    public function transfersIn(): HasMany
    {
        return $this->hasMany(PondTransfer::class, 'to_pond_id');
    }

    /** Sampled average weights over time (Phase 6 — FCR & Growth). */
    public function growthRecords(): HasMany
    {
        return $this->hasMany(GrowthRecord::class);
    }

    /** Pond inspections and their findings (Phase 6 — FCR & Growth). */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /** This pond's recurring inspection plan (Phase 6 — FCR & Growth). */
    public function inspectionSchedule(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(InspectionSchedule::class);
    }

    /** Transfers that moved fish OUT of this pond (Phase 5). */
    public function transfersFrom(): HasMany
    {
        return $this->hasMany(PondTransfer::class, 'from_pond_id');
    }

    /** Transfers that moved fish INTO this pond (Phase 5). */
    public function transfersTo(): HasMany
    {
        return $this->hasMany(PondTransfer::class, 'to_pond_id');
    }

    /*
     * When a future module lands (inspections, growth, ledger), add its
     * relationship here only once the table and model exist.
     */

    /* ---------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------*/

    /** Filter by one or more canonical status keys. */
    public function scopeStatus(Builder $query, string|array $status): Builder
    {
        return $query->whereIn('status', (array) $status);
    }

    /** Filter by pond type. */
    public function scopeOfType(Builder $query, int|string $typeId): Builder
    {
        return $query->where('pond_type_id', $typeId);
    }

    /** Free-text search across the fields a user actually searches by. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            // Escape LIKE wildcards so a user typing "%" or "_" searches for the
            // literal character instead of matching everything. The backslash must
            // be escaped first, otherwise it would double-escape the others.
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
            $like = '%' . $escaped . '%';

            $q->where('pond_number', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('location', 'like', $like)
                ->orWhere('water_source', 'like', $like);
        });
    }

    /* ---------------------------------------------------------------------
     | Stock helpers
    |---------------------------------------------------------------------*/

    /**
     * Live stock in the pond: total quantity stocked − mortality − harvested.
     *
     * This is the ONE definition of "how many fish are in this pond". It uses
     * aggregate sub-queries (no per-relationship loading), so it is safe to call
     * inside a list loop without N+1. The value is never negative because writes
     * are guarded by FishStockService, but it is clamped at zero defensively.
     */
    public function currentStock(): int
    {
        return app(\App\Services\Fish\FishStockService::class)->currentStock($this);
    }

    /** Whether the pond currently holds any live fish. */
    public function hasLiveStock(): bool
    {
        return $this->currentStock() > 0;
    }

    /**
     * Total feed given to this pond, in kg (Phase 4).
     *
     * A direct aggregate over `feed_usages`, so it is safe in a list loop. This
     * is the feed input to FCR (docs/BUSINESS_LOGIC.md §1).
     */
    public function totalFeedKg(): float
    {
        return (float) $this->feedUsages()->sum('quantity_kg');
    }

    /**
     * This pond's profit (Phase 5): total credits − total debits.
     *
     * A negative result is a genuine loss and is shown as one, never clamped to
     * zero (docs/BUSINESS_LOGIC.md §3). The arithmetic comes from
     * PondLedgerService, which applies LedgerRules — not from this model.
     */
    public function profit(): float
    {
        return app(\App\Services\Pond\PondLedgerService::class)->pondProfit($this);
    }

    /** The latest sampled average weight in grams, or null when never sampled. */
    public function latestAvgWeightG(): ?float
    {
        $value = $this->growthRecords()->orderByDesc('sampled_on')->orderByDesc('id')->value('avg_weight_g');

        return $value === null ? null : (float) $value;
    }

    /* ---------------------------------------------------------------------
     | Presentation helpers
    |---------------------------------------------------------------------*/

    /**
     * The full status definition from the catalogue, or null if the stored value
     * is unknown (which would be a data defect, not a display choice).
     *
     * @return array<string, mixed>|null
     */
    public function statusDefinition(): ?array
    {
        return config("ponds.statuses.{$this->status}");
    }

    /** Human label for the status, e.g. "Maintenance". */
    public function statusLabel(): string
    {
        return $this->statusDefinition()['label'] ?? Str::headline((string) $this->status);
    }

    /** Badge tone for the status (success | warning | danger | info | default). */
    public function statusTone(): string
    {
        return $this->statusDefinition()['tone'] ?? 'default';
    }

    /** Display-ready size, e.g. "12.5 decimal". */
    public function sizeDisplay(): string
    {
        return $this->measurementDisplay($this->size, $this->size_unit);
    }

    /** Display-ready depth, e.g. "1.8 m" — an em dash when not recorded. */
    public function depthDisplay(): string
    {
        return $this->measurementDisplay($this->depth, $this->depth_unit);
    }

    /** Symbol for the size unit, falling back to the stored value. */
    public function sizeUnitSymbol(): string
    {
        return config("ponds.size_units.{$this->size_unit}.symbol", $this->size_unit ?? '');
    }

    /** Symbol for the depth unit, falling back to the stored value. */
    public function depthUnitSymbol(): string
    {
        return config("ponds.depth_units.{$this->depth_unit}.symbol", $this->depth_unit ?? '');
    }

    /**
     * Format a decimal measurement for display.
     *
     * `decimal:3` casts to a string like "12.500"; trailing zeros are trimmed so
     * the UI reads "12.5" rather than a column of noise. A null measurement is
     * rendered as an em dash — never as "0" (docs/BUSINESS_LOGIC.md §9 rule 6).
     */
    private function measurementDisplay(mixed $value, ?string $unit): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $number = rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');

        $symbol = $unit ? $this->unitSymbolFor($unit) : '';

        return $symbol === '' ? $number : "{$number} {$symbol}";
    }

    /** Resolve a stored unit key to its short symbol. */
    private function unitSymbolFor(string $unit): string
    {
        return config("ponds.size_units.{$unit}.symbol")
            ?? config("ponds.depth_units.{$unit}.symbol")
            ?? $unit;
    }
}
