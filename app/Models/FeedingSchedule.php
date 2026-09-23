<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeedingSchedule — a PLANNED feeding (a meal in the schedule).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A schedule is a PLAN. It does NOT move feed stock. Stock is reduced only when a
 * feeding is actually performed and recorded as a `Feeding` (docs/BUSINESS_LOGIC.md §2).
 * `recurrence` is 'once' or 'daily'.
 */
class FeedingSchedule extends Model
{
    public const RECURRENCE_ONCE = 'once';

    public const RECURRENCE_DAILY = 'daily';

    protected $fillable = [
        'pond_id',
        'feed_type_id',
        'scheduled_on',
        'scheduled_time',
        'planned_quantity_kg',
        'recurrence',
        'is_active',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_on' => 'date',
            'planned_quantity_kg' => 'decimal:3',
            'is_active' => 'boolean',
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

    /** The actual feedings recorded against this plan. */
    public function feedings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Feeding::class);
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
        return $query->where('is_active', true);
    }

    public function scopeForPond(Builder $query, int|string $pondId): Builder
    {
        return $query->where('pond_id', $pondId);
    }

    /* ---------------------------------------------------------------------
     | Presentation
    |---------------------------------------------------------------------*/

    public function recurrenceLabel(): string
    {
        return config("finance.feed_schedule_recurrences.{$this->recurrence}.label", ucfirst((string) $this->recurrence));
    }

    /** Display-ready time, e.g. "10:00". */
    public function timeDisplay(): string
    {
        return $this->scheduled_time === null
            ? '—'
            : substr((string) $this->scheduled_time, 0, 5);
    }

    /** Display-ready planned quantity, e.g. "10 kg". */
    public function plannedDisplay(): string
    {
        $number = rtrim(rtrim(number_format((float) $this->planned_quantity_kg, 3, '.', ''), '0'), '.');

        return "{$number} kg";
    }
}
