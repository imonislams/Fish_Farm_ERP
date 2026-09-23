<?php

namespace App\Services\Fcr;

use App\Models\InspectionSchedule;
use App\Models\Pond;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Inspection schedule business logic.
 *
 * A schedule says "this pond should be inspected every N days". The due date is
 * always DERIVED here — never typed in — so it can never disagree with the
 * frequency that produced it:
 *
 *     next_due_on = (last_completed_on ?? created_at date) + frequency interval
 *
 * One schedule per pond is enforced by a unique index, so "is it due?" always has
 * a single answer.
 */
class InspectionScheduleService
{
    /**
     * Create or replace a pond's schedule.
     *
     * @param  array<string, mixed>  $data
     */
    public function setForPond(Pond $pond, array $data): InspectionSchedule
    {
        return DB::transaction(function () use ($pond, $data): InspectionSchedule {
            $frequency = $data['frequency'];

            $schedule = InspectionSchedule::query()->firstOrNew(['pond_id' => $pond->getKey()]);

            $schedule->frequency = $frequency;
            $schedule->is_active = (bool) ($data['is_active'] ?? true);

            if (array_key_exists('last_completed_on', $data)) {
                $schedule->last_completed_on = $data['last_completed_on'];
            }

            // Derived — a stored due date can never drift from the frequency.
            $schedule->next_due_on = $this->deriveNextDueOn(
                $frequency,
                $schedule->last_completed_on,
            );

            $schedule->save();

            return $schedule;
        });
    }

    /**
     * Mark a schedule as completed on a date and push the due date forward.
     *
     * Called by InspectionService when an inspection is recorded, INSIDE the same
     * transaction, so the schedule can never be left stale.
     */
    public function complete(InspectionSchedule $schedule, mixed $completedOn = null): InspectionSchedule
    {
        return DB::transaction(function () use ($schedule, $completedOn): InspectionSchedule {
            $date = $completedOn ?? now();

            $schedule->last_completed_on = $date;
            $schedule->next_due_on = $this->deriveNextDueOn($schedule->frequency, $date);
            $schedule->save();

            return $schedule->refresh();
        });
    }

    /**
     * Update a schedule's settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(InspectionSchedule $schedule, array $data): InspectionSchedule
    {
        return DB::transaction(function () use ($schedule, $data): InspectionSchedule {
            $schedule->frequency = $data['frequency'];
            $schedule->is_active = (bool) ($data['is_active'] ?? false);

            if (array_key_exists('last_completed_on', $data)) {
                $schedule->last_completed_on = $data['last_completed_on'];
            }

            $schedule->next_due_on = $this->deriveNextDueOn(
                $schedule->frequency,
                $schedule->last_completed_on,
            );

            $schedule->save();

            return $schedule->refresh();
        });
    }

    /** Remove a pond's schedule entirely. */
    public function delete(InspectionSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule): void {
            $schedule->delete();
        });
    }

    /**
     * Derive the next due date from a frequency key and a completion date.
     *
     * Anchors on the last completion when there is one, otherwise on today — so a
     * brand-new schedule is due one interval from now, not immediately overdue.
     */
    public function deriveNextDueOn(string $frequency, mixed $lastCompletedOn = null): string
    {
        $days = (int) config("fcr.frequencies.{$frequency}.days", 7);

        $anchor = $lastCompletedOn
            ? \Illuminate\Support\Carbon::parse($lastCompletedOn)
            : now();

        return $anchor->copy()->addDays($days)->toDateString();
    }

    /* ---------------------------------------------------------------------
     | Derived figures
    |---------------------------------------------------------------------*/

    /**
     * Farm-wide schedule counts by status: overdue / due_soon / scheduled / inactive.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = ['overdue' => 0, 'due_soon' => 0, 'scheduled' => 0, 'inactive' => 0];

        foreach (InspectionSchedule::query()->get() as $schedule) {
            $counts[$schedule->statusKey()]++;
        }

        return $counts;
    }

    /**
     * Schedules that are overdue or due soon, soonest first.
     *
     * @return Collection<int, InspectionSchedule>
     */
    public function dueOrOverdue(): Collection
    {
        return InspectionSchedule::query()
            ->active()
            ->with('pond:id,name,pond_number')
            ->whereDate('next_due_on', '<=', now()->addDays((int) config('fcr.due_soon_days', 2))->toDateString())
            ->orderBy('next_due_on')
            ->get();
    }

    /**
     * Ponds that have no schedule at all — honest coverage reporting.
     *
     * @return Collection<int, Pond>
     */
    public function pondsWithoutSchedule(): Collection
    {
        return Pond::query()
            ->whereDoesntHave('inspectionSchedule')
            ->orderBy('pond_number')
            ->get();
    }
}
