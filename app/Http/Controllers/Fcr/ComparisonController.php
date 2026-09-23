<?php

namespace App\Http\Controllers\Fcr;

use App\Http\Controllers\Controller;
use App\Models\Pond;
use App\Services\Fcr\FcrService;
use App\Services\Fcr\GrowthService;
use App\Services\Fcr\InspectionService;

/**
 * Pond Comparison — every pond's efficiency side by side.
 *
 * Each row carries the real FCR, growth gain and inspection record for its pond,
 * so the farm's best and worst performers are visible at a glance. A pond whose
 * FCR cannot be computed is shown as "—" WITH its reason — it is never ranked as
 * if it had a value.
 */
class ComparisonController extends Controller
{
    public function __construct(
        private readonly FcrService $fcr,
        private readonly GrowthService $growth,
        private readonly InspectionService $inspections,
    ) {}

    public function __invoke(): \Inertia\Response
    {
        $ponds = Pond::query()
            ->with('type:id,name')
            ->orderBy('pond_number')
            ->get();

        $pondIds = $ponds->pluck('id')->all();

        $fcrByPond = $this->fcr->forPonds($pondIds);
        $growthByPond = $this->growth->summariesForPonds($pondIds);
        $inspectionCounts = $this->inspections->countsForPonds($pondIds);

        $rows = $ponds->map(function (Pond $pond) use ($fcrByPond, $growthByPond, $inspectionCounts): array {
            $fcr = $fcrByPond[$pond->id] ?? null;
            $band = $fcr?->band() ?? 'unknown';

            return [
                'id' => $pond->id,
                'pond' => $pond->name,
                'pond_number' => $pond->pond_number,
                'type' => $pond->type?->name,
                'fcr_value' => $fcr?->fcr,
                'fcr_display' => $fcr?->display() ?? '—',
                'reason' => $fcr?->reason() ?? 'Not computed.',
                'band_tone' => config("fcr.fcr_bands.{$band}.tone", 'default'),
                'band_label' => config("fcr.fcr_bands.{$band}.label", '—'),
                'gain_kg' => ($fcr && $fcr->isAvailable()) ? $fcr->weightGainKg : 0,
                'live_stock' => $this->fcr->stockCount($pond),
                'feed_kg' => $this->fcr->feedConsumedKg($pond),
                'urls' => ['show' => route('ponds.show', $pond, absolute: false)],
            ];
        });

        $ranked = $rows
            ->filter(fn(array $r) => $r['fcr_value'] !== null)
            ->sortBy(fn(array $r) => $r['fcr_value'])
            ->values()
            ->map(fn (array $r, int $i): array => array_merge($r, ['rank' => $i + 1]))
            ->all();

        $unranked = $rows
            ->filter(fn(array $r) => $r['fcr_value'] === null)
            ->values()
            ->all();

        return \Inertia\Inertia::render('Fcr/Comparison', [
            'title' => 'Pond Comparison',
            'ranked' => $ranked,
            'unranked' => $unranked,
            'pondCount' => $ponds->count(),
        ]);
    }
}
