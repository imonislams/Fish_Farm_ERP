<?php

namespace App\Services\Feed;

use App\Models\Feeding;
use App\Models\FeedingSchedule;
use App\Models\FeedUsage;
use Illuminate\Support\Facades\DB;

/**
 * FeedingService — the business logic for meal schedules and actual feedings.
 *
 * THE CENTRAL RULE (docs/BUSINESS_LOGIC.md §2):
 *
 *   Scheduling feed   → NO stock change. A schedule is a plan.
 *   Completing a feed → writes a `feed_usages` row through FeedStockService, which
 *                       DECREASES feed stock and feeds FCR.
 *
 * So there is exactly ONE authority for feed stock (FeedStockService); this service
 * never keeps a stock figure of its own. Deleting a feeding restores the stock by
 * deleting the usage it produced (again through FeedStockService).
 */
final class FeedingService
{
    public function __construct(
        private readonly FeedStockService $feedStock,
    ) {}

    /* ---------------------------------------------------------------------
     | Schedules — plans only, no stock movement
    |---------------------------------------------------------------------*/

    /**
     * Create a meal schedule (a PLAN). This never touches feed stock.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSchedule(array $data): FeedingSchedule
    {
        $schedule = new FeedingSchedule;
        $schedule->fill([
            'pond_id' => $data['pond_id'],
            'feed_type_id' => $data['feed_type_id'],
            'scheduled_on' => $data['scheduled_on'],
            'scheduled_time' => $data['scheduled_time'],
            'planned_quantity_kg' => $data['planned_quantity_kg'],
            'recurrence' => $data['recurrence'] ?? FeedingSchedule::RECURRENCE_ONCE,
            'is_active' => $data['is_active'] ?? true,
            'note' => $data['note'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
        $schedule->save();

        return $schedule;
    }

    /**
     * Update a meal schedule (still a plan — no stock movement).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSchedule(FeedingSchedule $schedule, array $data): FeedingSchedule
    {
        $schedule->fill([
            'pond_id' => $data['pond_id'] ?? $schedule->pond_id,
            'feed_type_id' => $data['feed_type_id'] ?? $schedule->feed_type_id,
            'scheduled_on' => $data['scheduled_on'] ?? $schedule->scheduled_on,
            'scheduled_time' => $data['scheduled_time'] ?? $schedule->scheduled_time,
            'planned_quantity_kg' => $data['planned_quantity_kg'] ?? $schedule->planned_quantity_kg,
            'recurrence' => $data['recurrence'] ?? $schedule->recurrence,
            'is_active' => $data['is_active'] ?? $schedule->is_active,
            'note' => $data['note'] ?? $schedule->note,
        ]);
        $schedule->save();

        return $schedule;
    }

    /**
     * Delete a schedule. Its recorded feedings are kept (they are real history) but
     * detached from the plan, so deleting a plan never erases what actually happened.
     */
    public function deleteSchedule(FeedingSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule): void {
            $schedule->feedings()->update(['feeding_schedule_id' => null]);
            $schedule->delete();
        });
    }

    /* ---------------------------------------------------------------------
     | Feedings — an ACTUAL meal; this is what moves stock
    |---------------------------------------------------------------------*/

    /**
     * Record an ACTUAL feeding.
     *
     * A 'skipped' feeding consumes nothing and writes no stock movement. Any other
     * status writes a `feed_usages` row (stock OUT) through FeedStockService and
     * links it back here. The whole thing is atomic: if the stock guard refuses the
     * usage (feed would go negative), nothing is written.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \DomainException when the feed type cannot cover the consumption
     */
    public function recordFeeding(array $data): Feeding
    {
        return DB::transaction(function () use ($data): Feeding {
            $status = $data['status'] ?? Feeding::STATUS_COMPLETED;
            $consumed = (float) $data['consumed_quantity_kg'];

            // A skipped meal is recorded for completeness but moves no stock.
            $usage = null;

            if ($status !== Feeding::STATUS_SKIPPED && $consumed > 0) {
                // The stock guard here is authoritative — it runs inside the same
                // transaction, on a row-locked feed type (FeedStockService).
                $usage = $this->feedStock->recordUsage([
                    'pond_id' => $data['pond_id'],
                    'feed_type_id' => $data['feed_type_id'],
                    'quantity_kg' => $consumed,
                    'used_on' => $data['fed_on'],
                    'note' => 'Feeding' . (isset($data['feeding_schedule_id']) ? ' (scheduled meal)' : ''),
                    'created_by' => $data['created_by'] ?? null,
                ]);
            }

            $feeding = new Feeding;
            $feeding->fill([
                'pond_id' => $data['pond_id'],
                'feed_type_id' => $data['feed_type_id'],
                'feeding_schedule_id' => $data['feeding_schedule_id'] ?? null,
                'feed_usage_id' => $usage?->getKey(),
                'planned_quantity_kg' => $data['planned_quantity_kg'] ?? null,
                'consumed_quantity_kg' => $consumed,
                'fed_on' => $data['fed_on'],
                'fed_at' => $data['fed_at'] ?? null,
                'status' => $status,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $feeding->save();

            return $feeding;
        });
    }

    /**
     * Update a recorded feeding.
     *
     * Re-posts the underlying stock movement so the feed-stock figure always agrees
     * with the feeding (a change of quantity or of skipped-state is reflected).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \DomainException
     */
    public function updateFeeding(Feeding $feeding, array $data): Feeding
    {
        return DB::transaction(function () use ($feeding, $data): Feeding {
            $status = $data['status'] ?? $feeding->status;
            $consumed = (float) ($data['consumed_quantity_kg'] ?? $feeding->consumed_quantity_kg);
            $fedOn = $data['fed_on'] ?? $feeding->fed_on;

            // Remove the previous movement (restores stock) and re-post the new one.
            if ($feeding->usage !== null) {
                $this->feedStock->deleteOutMovement($feeding->usage);
                $feeding->feed_usage_id = null;
            }

            if ($status !== Feeding::STATUS_SKIPPED && $consumed > 0) {
                $usage = $this->feedStock->recordUsage([
                    'pond_id' => $data['pond_id'] ?? $feeding->pond_id,
                    'feed_type_id' => $data['feed_type_id'] ?? $feeding->feed_type_id,
                    'quantity_kg' => $consumed,
                    'used_on' => $fedOn,
                    'note' => 'Feeding (updated)',
                    'created_by' => $feeding->created_by,
                ]);
                $feeding->feed_usage_id = $usage->getKey();
            }

            $feeding->fill([
                'pond_id' => $data['pond_id'] ?? $feeding->pond_id,
                'feed_type_id' => $data['feed_type_id'] ?? $feeding->feed_type_id,
                'planned_quantity_kg' => $data['planned_quantity_kg'] ?? $feeding->planned_quantity_kg,
                'consumed_quantity_kg' => $consumed,
                'fed_on' => $fedOn,
                'fed_at' => $data['fed_at'] ?? $feeding->fed_at,
                'status' => $status,
                'note' => $data['note'] ?? $feeding->note,
            ]);
            $feeding->save();

            return $feeding;
        });
    }

    /**
     * Delete a recorded feeding, restoring the feed stock it consumed.
     */
    public function deleteFeeding(Feeding $feeding): void
    {
        DB::transaction(function () use ($feeding): void {
            if ($feeding->usage !== null) {
                $this->feedStock->deleteOutMovement($feeding->usage);
            }

            $feeding->delete();
        });
    }

    /* ---------------------------------------------------------------------
     | Derived figures for the schedule board
    |---------------------------------------------------------------------*/

    /**
     * Today's schedule board: each active schedule for the date, with any feeding
     * already recorded against it. All values are real records.
     *
     * @return array<int, array<string, mixed>>
     */
    public function boardForDate(string $date): array
    {
        $schedules = FeedingSchedule::query()
            ->active()
            ->with(['pond:id,name,pond_number', 'feedType:id,name,brand'])
            ->whereDate('scheduled_on', '<=', $date)
            ->where(function ($q) use ($date): void {
                // One-off plans for this day, or any daily plan that has started.
                $q->whereDate('scheduled_on', $date)
                    ->orWhere('recurrence', FeedingSchedule::RECURRENCE_DAILY);
            })
            ->orderBy('scheduled_time')
            ->get();

        $doneIds = Feeding::query()
            ->whereDate('fed_on', $date)
            ->whereNotNull('feeding_schedule_id')
            ->pluck('feeding_schedule_id')
            ->all();

        return $schedules->map(fn(FeedingSchedule $s): array => [
            'id' => $s->id,
            'pond' => $s->pond?->name,
            'pond_number' => $s->pond?->pond_number,
            'feed' => $s->feedType?->displayName(),
            'time' => $s->timeDisplay(),
            'planned_kg' => (float) $s->planned_quantity_kg,
            'recurrence' => $s->recurrenceLabel(),
            'done' => in_array($s->id, $doneIds, true),
            'urls' => [
                'record' => route('feed.feedings.create', ['schedule' => $s->id], absolute: false),
                'pond' => $s->pond ? route('ponds.show', $s->pond, absolute: false) : null,
            ],
        ])->all();
    }

    /** Schedules whose next due moment has passed and have no feeding today. */
    public function dueCount(?string $date = null): int
    {
        $date ??= now()->toDateString();

        $schedules = FeedingSchedule::query()
            ->active()
            ->whereDate('scheduled_on', '<=', $date)
            ->where(function ($q) use ($date): void {
                $q->whereDate('scheduled_on', $date)
                    ->orWhere('recurrence', FeedingSchedule::RECURRENCE_DAILY);
            })
            ->get();

        $doneIds = Feeding::query()
            ->whereDate('fed_on', $date)
            ->whereNotNull('feeding_schedule_id')
            ->pluck('feeding_schedule_id')
            ->all();

        return $schedules->reject(fn($s) => in_array($s->id, $doneIds, true))->count();
    }

    /** The user id to attribute a write to, or null when unauthenticated. */
    public function actorId(?int $userId = null): ?int
    {
        return $this->feedStock->actorId($userId);
    }
}
