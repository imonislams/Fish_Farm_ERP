<?php

namespace App\Http\Controllers\Fcr;

use App\Http\Controllers\Controller;
use App\Models\InspectionSchedule;
use App\Models\Pond;
use App\Services\Fcr\FcrService;
use App\Services\Fcr\GrowthService;
use App\Services\Fcr\InspectionScheduleService;
use App\Services\Fcr\InspectionService;

/**
 * FCR dashboard — the farm's feed-efficiency position at a glance.
 *
 * Every figure comes from the FCR services (FcrService, GrowthService,
 * InspectionService, InspectionScheduleService). Nothing is estimated, and an
 * unavailable FCR renders as "—" WITH its reason — never as 0
 * (docs/BUSINESS_LOGIC.md §1).
 */
class FcrController extends Controller
{
    public function __construct(
        private readonly FcrService $fcr,
        private readonly GrowthService $growth,
        private readonly InspectionService $inspections,
        private readonly InspectionScheduleService $schedules,
    ) {}

    public function __invoke(): \Inertia\Response
    {
        $ponds = Pond::query()
            ->with('type:id,name')
            ->orderBy('pond_number')
            ->get();

        $pondIds = $ponds->pluck('id')->all();

        // Batch FCR + growth for every pond — a fixed number of queries.
        $fcrByPond = $this->fcr->forPonds($pondIds);
        $growthByPond = $this->growth->summariesForPonds($pondIds);
        $inspectionCounts = $this->inspections->countsForPonds($pondIds);

        $bands = config('fcr.fcr_bands', []);

        // Pond rows carrying their own computed figures, so React does no maths.
        $rows = $ponds->map(function (Pond $pond) use ($fcrByPond, $inspectionCounts): array {
            $fcr = $fcrByPond[$pond->id] ?? null;
            $band = $fcr?->band() ?? 'unknown';

            return [
                'id' => $pond->id,
                'name' => $pond->name,
                'pond_number' => $pond->pond_number,
                'live_stock' => $this->fcr->stockCount($pond),
                'total_feed_kg' => $fcr?->totalFeedKg ?? 0,
                'weight_gain_kg' => ($fcr === null || ! $fcr->isAvailable()) ? null : $fcr->weightGainKg,
                'fcr_display' => $fcr?->display() ?? '—',
                'fcr_reason' => $fcr?->reason() ?? '',
                'fcr_tone' => config("fcr.fcr_bands.{$band}.tone", 'default'),
                'inspections_count' => $inspectionCounts[$pond->id]['count'] ?? 0,
                'urls' => [
                    'show' => route('ponds.show', $pond, absolute: false),
                ],
            ];
        })->values()->all();

        // Farm averages are only meaningful over ponds with a computable FCR —
        // averaging "—" would be inventing a number.
        $computed = collect($fcrByPond)->filter(fn($r) => $r->isAvailable());
        $averageFcr = $computed->isNotEmpty()
            ? round($computed->avg(fn($r) => $r->fcr), 4)
            : null;

        $bestPondId = $computed
            ->sortBy(fn($r) => $r->fcr)
            ->keys()
            ->first();

        $bestPond = $bestPondId ? $ponds->firstWhere('id', $bestPondId) : null;

        $averageBand = \App\Services\Fcr\FcrResult::bandFor($averageFcr);
        $averageHint = $averageFcr === null
            ? 'Not computable yet'
            : config("fcr.fcr_bands.{$averageBand}.label", $averageBand)
                . ' · ' . $computed->count() . ' of ' . $ponds->count() . ' ponds';

        return \Inertia\Inertia::render('Fcr/Index', [
            'title' => 'FCR Dashboard',
            'rows' => $rows,
            'pondCount' => $ponds->count(),
            'averageFcr' => $averageFcr,
            'averageBand' => $averageBand,
            'averageHint' => $averageHint,
            'computableCount' => $computed->count(),
            'bestPond' => $bestPond ? [
                'name' => $bestPond->name,
                'pond_number' => $bestPond->pond_number,
            ] : null,
            'inspectionStatusCounts' => $this->inspections->statusCounts(),
            'scheduleStatusCounts' => $this->schedules->statusCounts(),
            'scheduleTotal' => InspectionSchedule::query()->count(),
            'recentConcerns' => $this->inspections->concerning(5)
                ->map(fn ($c): array => [
                    'date' => $c->inspected_on?->toDateString(),
                    'pond' => $c->pond?->name,
                    'label' => $c->healthLabel(),
                    'tone' => $c->healthTone(),
                ])
                ->values()
                ->all(),
            'healthStatuses' => config('fcr.health_statuses', []),
            'fcrBands' => $bands,
        ]);
    }
}
