<?php

namespace App\Http\Controllers\Fcr;

use App\Http\Controllers\Controller;
use App\Models\FeedUsage;
use App\Models\Pond;
use App\Services\Fcr\FcrService;
use App\Services\Fcr\GrowthService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Feed vs Growth — feed consumed against the weight gain it produced.
 *
 * This is the FCR story told per pond, month by month: how much feed went in,
 * how much weight came out, and the ratio between them. Every figure is a real
 * aggregate; an unavailable ratio renders as "—" with its reason.
 */
class FeedGrowthController extends Controller
{
    public function __construct(
        private readonly FcrService $fcr,
        private readonly GrowthService $growth,
    ) {}

    public function __invoke(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');
        $months = (int) $request->query('months', 6);
        $months = in_array($months, [3, 6, 12], true) ? $months : 6;

        $ponds = Pond::query()->orderBy('pond_number')->get(['id', 'pond_number', 'name']);

        $feedSeries = [];
        $pondRows = [];

        $selected = $pondId ? $ponds->firstWhere('id', (int) $pondId) : null;

        foreach ($ponds as $pond) {
            $fcr = $this->fcr->forPond($pond);
            $growth = $this->growth->summaryForPond($pond);
            $band = $fcr?->band() ?? 'unknown';

            $pondRows[] = [
                'pond' => $pond->name,
                'feed_kg' => $this->fcr->feedConsumedKg($pond),
                'first_g' => $growth['first_g'] ?? null,
                'latest_g' => $growth['latest_g'] ?? null,
                'gain_kg' => ($fcr && $fcr->isAvailable()) ? $fcr->weightGainKg : null,
                'fcr_display' => $fcr?->display() ?? '—',
                'fcr_reason' => $fcr?->reason() ?? '',
                'fcr_tone' => config("fcr.fcr_bands.{$band}.tone", 'default'),
            ];
        }

        if ($selected) {
            $feedSeries = $this->monthlyFeedSeries($selected, $months)->all();
        }

        return \Inertia\Inertia::render('Fcr/FeedGrowth', [
            'title' => 'Feed vs Growth',
            'pondRows' => $pondRows,
            'pondOptions' => $ponds->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"])->all(),
            'selectedPond' => $selected ? [
                'id' => $selected->id,
                'name' => $selected->name,
                'pond_number' => $selected->pond_number,
            ] : null,
            'feedSeries' => $feedSeries,
            'filters' => ['pond' => $pondId, 'months' => $months],
        ]);
    }

    /**
     * Feed consumed per month for a pond, oldest first — the bars a chart plots.
     *
     * @return Collection<int, array{label: string, month: string, feed_kg: float}>
     */
    private function monthlyFeedSeries(Pond $pond, int $months): Collection
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = FeedUsage::query()
            ->where('pond_id', $pond->getKey())
            ->whereDate('used_on', '>=', $start->toDateString())
            ->selectRaw("DATE_FORMAT(used_on, '%Y-%m') as ym, SUM(quantity_kg) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $series = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $cursor = now()->subMonths($i)->startOfMonth();
            $key = $cursor->format('Y-m');
            $total = (float) $rows->get($key, 0);

            $series->push([
                'label' => $cursor->format('M Y'),
                'month' => $key,
                'feed_kg' => round($total, 3),
            ]);
        }

        return $series;
    }
}
