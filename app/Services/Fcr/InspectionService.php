<?php

namespace App\Services\Fcr;

use App\Models\Inspection;
use App\Models\InspectionSchedule;
use App\Models\Pond;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Inspection business logic.
 *
 * An inspection records what was measured and the conclusion reached. Two rules
 * matter:
 *
 *   1. A reading that was NOT taken stays NULL ("—"), never 0. Storing 0 for an
 *      unmeasured parameter would be a lie that pollutes every later average
 *      (docs/BUSINESS_LOGIC.md §9 rule 6).
 *   2. Recording an inspection for a pond that has a schedule ADVANCES that
 *      schedule (last_completed_on = inspection date, next_due_on recomputed) —
 *      in the same transaction — so "is it due?" can never go stale.
 */
class InspectionService
{
    public function __construct(
        private readonly InspectionScheduleService $schedules,
    ) {}

    /**
     * Record an inspection.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): Inspection
    {
        return DB::transaction(function () use ($data): Inspection {
            $inspection = new Inspection;
            $inspection->fill([
                'pond_id' => $data['pond_id'],
                'inspected_on' => $data['inspected_on'],
                'inspected_by' => $data['inspected_by'] ?? null,
                // Unmeasured parameters are stored as null, never 0.
                'water_ph' => $data['water_ph'] ?? null,
                'water_temp_c' => $data['water_temp_c'] ?? null,
                'dissolved_oxygen' => $data['dissolved_oxygen'] ?? null,
                'ammonia' => $data['ammonia'] ?? null,
                'turbidity' => $data['turbidity'] ?? null,
                'health_status' => $data['health_status'],
                'action_taken' => $data['action_taken'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $inspection->save();

            // Completing an inspection advances the pond's schedule, if it has one.
            $schedule = InspectionSchedule::query()
                ->where('pond_id', $inspection->pond_id)
                ->first();

            if ($schedule !== null) {
                $this->schedules->complete($schedule, $inspection->inspected_on);
            }

            return $inspection;
        });
    }

    /**
     * Update an inspection.
     *
     * The schedule is NOT re-advanced on an edit: an edit corrects a record, it
     * does not represent a new inspection.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Inspection $inspection, array $data): Inspection
    {
        return DB::transaction(function () use ($inspection, $data): Inspection {
            $inspection->fill([
                'pond_id' => $data['pond_id'],
                'inspected_on' => $data['inspected_on'],
                'inspected_by' => $data['inspected_by'] ?? null,
                'water_ph' => $data['water_ph'] ?? null,
                'water_temp_c' => $data['water_temp_c'] ?? null,
                'dissolved_oxygen' => $data['dissolved_oxygen'] ?? null,
                'ammonia' => $data['ammonia'] ?? null,
                'turbidity' => $data['turbidity'] ?? null,
                'health_status' => $data['health_status'],
                'action_taken' => $data['action_taken'] ?? null,
                'note' => $data['note'] ?? null,
            ])->save();

            return $inspection->refresh();
        });
    }

    /** Delete an inspection. */
    public function delete(Inspection $inspection): void
    {
        DB::transaction(function () use ($inspection): void {
            $inspection->delete();
        });
    }

    /* ---------------------------------------------------------------------
     | Derived figures
    |---------------------------------------------------------------------*/

    /**
     * How many inspections each pond has had, and when the last one was.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, array{count: int, last_on: ?string}>
     */
    public function countsForPonds(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        $rows = Inspection::query()
            ->whereIn('pond_id', $pondIds)
            ->selectRaw('pond_id, COUNT(*) as aggregate, MAX(inspected_on) as last_on')
            ->groupBy('pond_id')
            ->get();

        $out = [];

        foreach ($pondIds as $id) {
            $row = $rows->firstWhere('pond_id', $id);

            $out[$id] = [
                'count' => (int) ($row->aggregate ?? 0),
                'last_on' => $row->last_on ?? null,
            ];
        }

        return $out;
    }

    /**
     * Inspections that flagged a concern (warning / critical), newest first.
     *
     * @return Collection<int, Inspection>
     */
    public function concerning(?int $limit = 10): Collection
    {
        return Inspection::query()
            ->concerning()
            ->with('pond:id,name,pond_number')
            ->orderByDesc('inspected_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Farm-wide inspection counts grouped by health status.
     *
     * @return array<string, int>  status key => count
     */
    public function statusCounts(): array
    {
        $counts = Inspection::query()
            ->selectRaw('health_status, COUNT(*) as aggregate')
            ->groupBy('health_status')
            ->pluck('aggregate', 'health_status');

        $out = [];

        // Every status is present (defaulting to 0) so the UI is stable.
        foreach (array_keys(config('fcr.health_statuses', [])) as $key) {
            $out[$key] = (int) $counts->get($key, 0);
        }

        return $out;
    }

    /** The user id to attribute a write to, or null when unauthenticated. */
    public function actorId(?int $userId = null): ?int
    {
        return $userId ?? auth()->id();
    }
}
