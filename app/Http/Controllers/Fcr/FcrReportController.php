<?php

namespace App\Http\Controllers\Fcr;

use App\Http\Controllers\Controller;
use App\Models\Pond;
use App\Services\Fcr\FcrService;
use App\Services\Fcr\GrowthService;
use App\Services\Fcr\InspectionService;
use Illuminate\Http\Request;

/**
 * FCR Reports — a printable, date-ranged FCR and growth report.
 *
 * ALL report calculation lives in the FCR services; this controller only shapes
 * the request and hands the computed rows to the view. An unavailable FCR is
 * reported as "—" with its reason, so the report never asserts an efficiency the
 * data does not support (docs/BUSINESS_LOGIC.md §1).
 */
class FcrReportController extends Controller
{
    public function __construct(
        private readonly FcrService $fcr,
        private readonly GrowthService $growth,
        private readonly InspectionService $inspections,
    ) {}

    public function __invoke(Request $request): \Inertia\Response
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $pondId = $request->query('pond');

        $ponds = Pond::query()
            ->when($pondId, fn($q) => $q->where('id', $pondId))
            ->orderBy('pond_number')
            ->get();

        $rows = $ponds->map(function (Pond $pond) use ($from, $to): array {
            $result = $this->fcr->forPond($pond, $from, $to);
            $inputs = $this->fcr->inputs($pond);
            $band = $result->band();

            return [
                'pond' => $pond->name,
                'pond_number' => $pond->pond_number,
                'stock_count' => $inputs['stock_count'],
                'feed_kg' => $inputs['feed_consumed_kg'],
                'start_g' => $inputs['starting_avg_weight_g'],
                'current_g' => $inputs['current_avg_weight_g'],
                'gain_kg' => $inputs['weight_gain_kg'],
                'fcr_display' => $result->display(),
                'reason' => $result->reason(),
                'band_tone' => config("fcr.fcr_bands.{$band}.tone", 'default'),
            ];
        })->values();

        $computable = $rows->filter(fn(array $r) => $r['fcr_display'] !== '—');

        $summary = [
            'ponds' => $rows->count(),
            'with_fcr' => $computable->count(),
            'average_fcr' => $computable->isNotEmpty()
                ? round($computable->avg(fn(array $r) => (float) $r['fcr_display']), 4)
                : null,
            'total_feed_kg' => round($rows->sum('feed_kg'), 3),
            'total_gain_kg' => round($rows->sum('gain_kg'), 3),
        ];

        return \Inertia\Inertia::render('Fcr/Reports', [
            'title' => 'FCR Reports',
            'rows' => $rows->all(),
            'summary' => $summary,
            'filters' => ['from' => $from, 'to' => $to, 'pond' => $pondId],
            'pondOptions' => Pond::query()->orderBy('pond_number')
                ->get(['id', 'pond_number', 'name'])
                ->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"])
                ->all(),
            'healthStatuses' => config('fcr.health_statuses', []),
            'inspectionStatusCounts' => $this->inspections->statusCounts(),
        ]);
    }
}
