<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feeding — an ACTUAL feeding performed (real feed consumption).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * Recording a feeding writes a `feed_usages` row through FeedStockService, so it
 * decreases feed stock and feeds FCR. This table adds the meal context (when, and
 * against which plan). It holds no stock figure of its own — FeedStockService is
 * the single authority (docs/BUSINESS_LOGIC.md §2).
 */
class Feeding extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'pond_id',
        'feed_type_id',
        'feeding_schedule_id',
        'feed_usage_id',
        'planned_quantity_kg',
        'consumed_quantity_kg',
        'fed_on',
        'fed_at',
        'status',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity_kg' => 'decimal:3',
            'consumed_quantity_kg' => 'decimal:3',
            'fed_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
    |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function feedType(): BelongsTo
    {
        return $this->belongsTo(FeedType::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(FeedingSchedule::class, 'feeding_schedule_id');
    }

    /** The stock movement this feeding produced. */
    public function usage(): BelongsTo
    {
        return $this->belongsTo(FeedUsage::class, 'feed_usage_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------------------------------------------------------------
     | Scopes
    |---------------------------------------------------------------------*/

    public function scopeForPond(Builder $query, int|string $pondId): Builder
    {
        return $query->where('pond_id', $pondId);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('fed_on', $date);
    }

    /* ---------------------------------------------------------------------
     | Presentation
    |---------------------------------------------------------------------*/

    public function statusDefinition(): ?array
    {
        return config("finance.feeding_statuses.{$this->status}");
    }

    public function statusLabel(): string
    {
        return $this->statusDefinition()['label'] ?? ucfirst((string) $this->status);
    }

    public function statusTone(): string
    {
        return $this->statusDefinition()['tone'] ?? 'default';
    }

    public function consumedDisplay(): string
    {
        $number = rtrim(rtrim(number_format((float) $this->consumed_quantity_kg, 3, '.', ''), '0'), '.');

        return "{$number} kg";
    }

    public function timeDisplay(): string
    {
        return $this->fed_at === null ? '—' : substr((string) $this->fed_at, 0, 5);
    }
}
