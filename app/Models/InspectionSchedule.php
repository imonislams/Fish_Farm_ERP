<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InspectionSchedule — the recurring inspection plan for a pond.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `next_due_on` is DERIVED by InspectionScheduleService from the last completion
 * (or creation date) plus the frequency interval — never typed in — so it can
 * never disagree with the frequency that produced it.
 *
 * There is exactly one schedule per pond (a unique index enforces it): a pond
 * inspected on two rhythms would make "is it due?" ambiguous.
 */
class InspectionSchedule extends Model
{
    protected $fillable = [
        'pond_id',
        'frequency',
        'next_due_on',
        'last_completed_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_due_on' => 'date',
            'last_completed_on' => 'date',
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

    /* ---------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------*/

    /** Only active schedules. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Schedules whose due date has passed. */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereDate('next_due_on', '<', now()->toDateString());
    }

    /** Schedules due today or within the configured "due soon" window. */
    public function scopeDueSoon(Builder $query): Builder
    {
        $window = (int) config('fcr.due_soon_days', 2);

        return $query->whereDate('next_due_on', '>=', now()->toDateString())
            ->whereDate('next_due_on', '<=', now()->addDays($window)->toDateString());
    }

    /* ---------------------------------------------------------------------
     | Behaviour / presentation
    |---------------------------------------------------------------------*/

    /** Human label for the frequency, e.g. "Weekly". */
    public function frequencyLabel(): string
    {
        return config("fcr.frequencies.{$this->frequency}.label", $this->frequency);
    }

    /** The interval in days for the stored frequency. */
    public function frequencyDays(): int
    {
        return (int) config("fcr.frequencies.{$this->frequency}.days", 7);
    }

    /** True when the due date has passed. */
    public function isOverdue(): bool
    {
        return $this->is_active
            && $this->next_due_on !== null
            && $this->next_due_on->isBefore(now()->startOfDay());
    }

    /** True when due today or within the configured window. */
    public function isDueSoon(): bool
    {
        if (! $this->is_active || $this->next_due_on === null) {
            return false;
        }

        $window = (int) config('fcr.due_soon_days', 2);

        return $this->next_due_on->isToday()
            || $this->next_due_on->betweenIncluded(now()->startOfDay(), now()->addDays($window)->startOfDay());
    }

    /** True when nothing is due yet. */
    public function isScheduled(): bool
    {
        return $this->is_active && $this->next_due_on !== null && ! $this->isOverdue() && ! $this->isDueSoon();
    }

    /** Days until due (negative when overdue). Null when there is no due date. */
    public function daysUntilDue(): ?int
    {
        if ($this->next_due_on === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->next_due_on->startOfDay(), false);
    }

    /** Status key for display: overdue | due_soon | scheduled | inactive. */
    public function statusKey(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->isOverdue()) {
            return 'overdue';
        }

        if ($this->isDueSoon()) {
            return 'due_soon';
        }

        return 'scheduled';
    }

    /** Human label for the schedule status. */
    public function statusLabel(): string
    {
        return match ($this->statusKey()) {
            'overdue' => 'Overdue',
            'due_soon' => 'Due soon',
            'scheduled' => 'Scheduled',
            default => 'Inactive',
        };
    }

    /** Badge tone for the schedule status. */
    public function statusTone(): string
    {
        return match ($this->statusKey()) {
            'overdue' => 'danger',
            'due_soon' => 'warning',
            'scheduled' => 'success',
            default => 'default',
        };
    }
}
