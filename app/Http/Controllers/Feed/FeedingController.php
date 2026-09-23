<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feed\SaveFeedingScheduleRequest;
use App\Http\Requests\Feed\StoreFeedingRequest;
use App\Models\Feeding;
use App\Models\FeedingSchedule;
use App\Models\FeedType;
use App\Models\Pond;
use App\Services\Feed\FeedingService;
use App\Services\Feed\FeedStockService;
use App\Services\Notification\NotificationService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Feeding — meal schedules (plans) and actual feedings (which move stock).
 *
 * THE KEY RULE: creating a schedule never changes feed stock; recording a feeding
 * writes a feed_usages row through FeedStockService, which is the single authority
 * for feed stock (docs/BUSINESS_LOGIC.md §2).
 */
class FeedingController extends Controller
{
    public function __construct(
        private readonly FeedingService $feedings,
        private readonly FeedStockService $feedStock,
        private readonly NotificationService $notifications,
    ) {}

    /** The feeding board: today's schedule + recently recorded meals. */
    public function index(Request $request): \Inertia\Response
    {
        $date = $request->query('date') ?: now()->toDateString();

        // Generate "feeding due" alerts for today on demand (idempotent per day).
        $this->notifications->checkDueFeedings();

        $recent = Feeding::query()
            ->with(['pond:id,name,pond_number', 'feedType:id,name,brand'])
            ->orderByDesc('fed_on')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return \Inertia\Inertia::render('Feed/Feedings/Index', [
            'title' => 'Feeding',
            'date' => $date,
            'board' => $this->feedings->boardForDate($date),
            'dueCount' => $this->feedings->dueCount($date),
            'recent' => $recent->map(fn(Feeding $f): array => [
                'id' => $f->id,
                'date' => $f->fed_on?->format('Y-m-d'),
                'time' => $f->timeDisplay(),
                'pond' => $f->pond?->name,
                'feed' => $f->feedType?->displayName(),
                'consumed' => (float) $f->consumed_quantity_kg,
                'status' => $f->statusLabel(),
                'status_tone' => $f->statusTone(),
                'urls' => [
                    'destroy' => route('feed.feedings.destroy', $f, absolute: false),
                ],
            ])->all(),
            'filters' => ['date' => $date],
        ]);
    }

    /** The schedule list (plans). */
    public function schedules(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');

        $schedules = FeedingSchedule::query()
            ->with(['pond:id,name,pond_number', 'feedType:id,name,brand'])
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->orderByDesc('scheduled_on')
            ->orderBy('scheduled_time')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Feed/Schedules/Index', [
            'title' => 'Feeding Schedules',
            'schedules' => [
                'data' => collect($schedules->items())->map(fn(FeedingSchedule $s): array => [
                    'id' => $s->id,
                    'date' => $s->scheduled_on?->format('Y-m-d'),
                    'time' => $s->timeDisplay(),
                    'pond' => $s->pond?->name,
                    'pond_number' => $s->pond?->pond_number,
                    'feed' => $s->feedType?->displayName(),
                    'planned' => (float) $s->planned_quantity_kg,
                    'recurrence' => $s->recurrenceLabel(),
                    'is_active' => (bool) $s->is_active,
                    'recorded_count' => $s->feedings()->count(),
                    'urls' => [
                        'edit' => route('feed.schedules.edit', $s, absolute: false),
                        'destroy' => route('feed.schedules.destroy', $s, absolute: false),
                        'record' => route('feed.feedings.create', ['schedule' => $s->id], absolute: false),
                    ],
                ])->all(),
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'total' => $schedules->total(),
                'from' => $schedules->firstItem(),
                'to' => $schedules->lastItem(),
                'links' => $schedules->linkCollection()->toArray(),
            ],
            'options' => ['pondOptions' => $this->pondOptions()],
            'filters' => ['pond' => $pondId],
        ]);
    }

    /** Show the create-schedule form. */
    public function scheduleCreate(): \Inertia\Response
    {
        Gate::authorize('create', FeedingSchedule::class);

        return \Inertia\Inertia::render('Feed/Schedules/Create', [
            'title' => 'New Feeding Schedule',
            'options' => [
                'pondOptions' => $this->pondOptions(),
                'typeOptions' => $this->typeOptions(),
                'recurrenceOptions' => collect(config('finance.feed_schedule_recurrences', []))
                    ->map(fn(array $m): string => $m['label'])->all(),
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a schedule (a PLAN — no stock change). */
    public function scheduleStore(SaveFeedingScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->feedings->actorId();

        $schedule = $this->feedings->createSchedule($data);

        // A real notification that a plan exists (never a fake one).
        $this->notifications->feedingScheduled(
            $schedule->pond->name,
            $schedule->timeDisplay(),
            $schedule->feedType?->name,
        );

        return redirect()
            ->route('feed.schedules.index')
            ->with('success', "Feeding scheduled for \"{$schedule->pond->name}\" at {$schedule->timeDisplay()}.");
    }

    /** Show the edit-schedule form. */
    public function scheduleEdit(FeedingSchedule $schedule): \Inertia\Response
    {
        Gate::authorize('update', $schedule);

        return \Inertia\Inertia::render('Feed/Schedules/Edit', [
            'title' => 'Edit — Feeding Schedule',
            'schedule' => [
                'id' => $schedule->id,
                'pond_id' => $schedule->pond_id,
                'feed_type_id' => $schedule->feed_type_id,
                'scheduled_on' => $schedule->scheduled_on?->format('Y-m-d'),
                'scheduled_time' => $schedule->timeDisplay(),
                'planned_quantity_kg' => (float) $schedule->planned_quantity_kg,
                'recurrence' => $schedule->recurrence,
                'is_active' => (bool) $schedule->is_active,
                'note' => $schedule->note,
                'recorded_count' => $schedule->feedings()->count(),
            ],
            'options' => [
                'pondOptions' => $this->pondOptions(),
                'typeOptions' => $this->typeOptions(),
                'recurrenceOptions' => collect(config('finance.feed_schedule_recurrences', []))
                    ->map(fn(array $m): string => $m['label'])->all(),
            ],
        ]);
    }

    /** Persist schedule changes (still a plan). */
    public function scheduleUpdate(SaveFeedingScheduleRequest $request, FeedingSchedule $schedule): RedirectResponse
    {
        $this->feedings->updateSchedule($schedule, $request->validated());

        return redirect()
            ->route('feed.schedules.index')
            ->with('success', 'Feeding schedule updated.');
    }

    /** Delete a schedule (recorded feedings are kept, detached from the plan). */
    public function scheduleDestroy(Request $request, FeedingSchedule $schedule): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $schedule);

        $this->feedings->deleteSchedule($schedule);

        return AsyncResponse::ok($request, 'Feeding schedule deleted.', 'feed.schedules.index');
    }

    /** Show the record-a-feeding form (optionally pre-filled from a schedule). */
    public function feedingCreate(Request $request): \Inertia\Response
    {
        Gate::authorize('create', Feeding::class);

        $schedule = null;
        $scheduleId = $request->query('schedule');

        if ($scheduleId) {
            $found = FeedingSchedule::query()->with('pond:id,name', 'feedType:id,name,brand')->find($scheduleId);

            if ($found !== null) {
                $schedule = [
                    'id' => $found->id,
                    'pond_id' => $found->pond_id,
                    'feed_type_id' => $found->feed_type_id,
                    'planned_quantity_kg' => (float) $found->planned_quantity_kg,
                    'pond' => $found->pond?->name,
                    'feed' => $found->feedType?->displayName(),
                    'scheduled_on' => $found->scheduled_on?->format('Y-m-d'),
                ];
            }
        }

        return \Inertia\Inertia::render('Feed/Feedings/Create', [
            'title' => 'Record Feeding',
            'schedule' => $schedule,
            'options' => [
                'pondOptions' => $this->pondOptions(),
                'typeOptions' => $this->typeOptions(),
                'statusOptions' => collect(config('finance.feeding_statuses', []))
                    ->map(fn(array $m): string => $m['label'])->all(),
                // Real stock per feed type, shown as a hint next to the quantity.
                'stockByType' => $this->feedStock->stockForTypes(FeedType::query()->pluck('id')->all()),
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Record an ACTUAL feeding (this is what moves feed stock). */
    public function feedingStore(StoreFeedingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->feedings->actorId();

        try {
            $feeding = $this->feedings->recordFeeding($data);
        } catch (\DomainException $e) {
            // The stock guard is authoritative even if the form was bypassed.
            return back()->withInput()->with('error', $e->getMessage());
        }

        // A real notification that feed was consumed (skip a skipped meal).
        if ($feeding->status !== \App\Models\Feeding::STATUS_SKIPPED) {
            $this->notifications->feedingCompleted(
                $feeding->pond->name,
                (float) $feeding->consumed_quantity_kg,
                $feeding->feedType?->name,
            );
        }

        return redirect()
            ->route('feed.feedings.index')
            ->with('success', "Feeding recorded for \"{$feeding->pond->name}\" ({$feeding->consumedDisplay()}).");
    }

    /** Delete a feeding (restores the feed it consumed). */
    public function feedingDestroy(Request $request, Feeding $feeding): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $feeding);

        $this->feedings->deleteFeeding($feeding);

        return AsyncResponse::ok($request, 'Feeding record deleted.', 'feed.feedings.index');
    }

    /**
     * Ponds selectable in a form/filter: id => "P-01 — Pond name".
     *
     * @return array<int, string>
     */
    private function pondOptions(): array
    {
        return Pond::query()
            ->orderBy('pond_number')
            ->get(['id', 'pond_number', 'name'])
            ->mapWithKeys(fn(Pond $p): array => [$p->id => "{$p->pond_number} — {$p->name}"])
            ->all();
    }

    /**
     * Feed types selectable: id => "Name (Brand)".
     *
     * @return array<int, string>
     */
    private function typeOptions(): array
    {
        return FeedType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'brand'])
            ->mapWithKeys(fn(FeedType $t): array => [$t->id => $t->displayName()])
            ->all();
    }
}
