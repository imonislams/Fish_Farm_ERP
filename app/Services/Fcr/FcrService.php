<?php

namespace App\Services\Fcr;

use App\Models\FeedUsage;
use App\Models\FishStocking;
use App\Models\GrowthRecord;
use App\Models\Pond;

/**
 * FCR assembly — gathers the inputs and hands them to FcrCalculator.
 *
 * DIVISION OF RESPONSIBILITY:
 *   - FcrCalculator owns the FORMULA and every edge case (never divides by zero).
 *   - This service owns the DATA GATHERING: which feed is counted, which weights
 *     are used, over what period.
 *
 * FORMULA (docs/BUSINESS_LOGIC.md §1 — authoritative):
 *
 *     FCR = Total Feed Consumed (kg) / Weight Gain (kg)
 *     Weight Gain (kg) = (current avg weight − starting avg weight) × stock count / 1000
 *
 * An unavailable FCR is returned with its state and reason — never a bare 0.
 */
class FcrService
{
    public function __construct(
        private readonly FcrCalculator $calculator,
    ) {}

    /**
     * FCR for a pond.
     *
     * Inputs and how they are chosen:
     *   - Feed consumed: Σ `feed_usages.quantity_kg` for the pond (optionally a
     *     date range), because feed given to the pond is what the fish ate.
     *   - Starting weight: the average weight of the EARLIEST stocking for the
     *     pond — the weight the fish arrived at.
     *   - Current weight: the LATEST `growth_records.avg_weight_g` sample.
     *   - Stock count: the pond's live stock (the modern FishStockService figure),
     *     so a pond that has been harvested heavily does not report a weight gain
     *     for fish that are no longer there.
     *
     * Every missing input flows through to the calculator, which returns an
     * explicit unavailable state with a human reason.
     *
     * @param  string|null  $from  ISO date, inclusive (null = all time)
     * @param  string|null  $to    ISO date, inclusive (null = all time)
     */
    public function forPond(Pond $pond, ?string $from = null, ?string $to = null): FcrResult
    {
        return $this->calculator->calculate(
            totalFeedKg: $this->feedConsumedKg($pond, $from, $to),
            startingAvgWeightG: $this->startingAvgWeightG($pond),
            currentAvgWeightG: $this->currentAvgWeightG($pond),
            stockedCount: $this->stockCount($pond),
        );
    }

    /**
     * FCR for several ponds in a fixed number of queries (no N+1).
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, FcrResult>  pond id => result
     */
    public function forPonds(array $pondIds): array
    {
        $results = [];

        foreach ($pondIds as $id) {
            $pond = Pond::find($id);

            if ($pond === null) {
                continue;
            }

            $results[$id] = $this->forPond($pond);
        }

        return $results;
    }

    /**
     * FCR for several ponds in a FIXED number of queries, safe for a list page.
     *
     * Gathers all four inputs with grouped queries (feed sums, the earliest
     * stocking weight per pond, the latest growth sample per pond) instead of the
     * per-pond queries forPond() issues, then runs the SAME FcrCalculator — so the
     * figure is identical to the detail page, only cheaper. Used by the pond list.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, FcrResult>  pond id => result
     */
    public function forPondsBatch(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        // 1. Feed consumed per pond (one grouped query).
        $feed = FeedUsage::query()
            ->whereIn('pond_id', $pondIds)
            ->selectRaw('pond_id, SUM(quantity_kg) as aggregate')
            ->groupBy('pond_id')
            ->pluck('aggregate', 'pond_id');

        // 2. Earliest stocking date per pond (one grouped query).
        $earliestDates = FishStocking::query()
            ->whereIn('pond_id', $pondIds)
            ->whereNotNull('avg_weight_g')
            ->selectRaw('pond_id, MIN(stocked_on) as earliest')
            ->groupBy('pond_id')
            ->pluck('earliest', 'pond_id');

        // 3. Weighted starting weight on each pond's earliest stocking date.
        $startingWeights = [];
        if ($earliestDates->isNotEmpty()) {
            $rows = FishStocking::query()
                ->whereIn('pond_id', $pondIds)
                ->whereNotNull('avg_weight_g')
                ->get(['pond_id', 'stocked_on', 'quantity', 'avg_weight_g']);

            $acc = [];
            foreach ($rows as $r) {
                $date = $r->stocked_on?->toDateString();
                if (($earliestDates[$r->pond_id] ?? null) !== $date) {
                    continue;
                }
                $acc[$r->pond_id]['qty'] = ($acc[$r->pond_id]['qty'] ?? 0) + (int) $r->quantity;
                $acc[$r->pond_id]['wt'] = ($acc[$r->pond_id]['wt'] ?? 0)
                    + (float) $r->avg_weight_g * (int) $r->quantity;
            }
            foreach ($acc as $pid => $v) {
                $startingWeights[$pid] = $v['qty'] > 0
                    ? round($v['wt'] / $v['qty'], 2)
                    : 0.0;
            }
        }

        // 4. Latest sampled average weight per pond (one query, reduced in PHP).
        $latestWeights = GrowthRecord::query()
            ->whereIn('pond_id', $pondIds)
            ->orderBy('sampled_on')
            ->orderBy('id')
            ->get(['pond_id', 'avg_weight_g'])
            ->groupBy('pond_id')
            ->map(fn ($rows) => (float) $rows->last()->avg_weight_g);

        // 5. Live stock per pond (the modern FishStockService figure).
        $stock = app(\App\Services\Fish\FishStockService::class)->stockForPonds($pondIds);

        $results = [];
        foreach ($pondIds as $id) {
            $results[$id] = $this->calculator->calculate(
                totalFeedKg: (float) $feed->get($id, 0),
                startingAvgWeightG: (float) ($startingWeights[$id] ?? 0.0),
                currentAvgWeightG: (float) $latestWeights->get($id, 0.0),
                stockedCount: (int) ($stock[$id] ?? 0),
            );
        }

        return $results;
    }

    /* ---------------------------------------------------------------------
     | Input gathering
    |---------------------------------------------------------------------*/

    /** Total feed given to the pond (kg), optionally within a date range. */
    public function feedConsumedKg(Pond $pond, ?string $from = null, ?string $to = null): float
    {
        return (float) FeedUsage::query()
            ->where('pond_id', $pond->getKey())
            ->when($from, fn($q) => $q->whereDate('used_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('used_on', '<=', $to))
            ->sum('quantity_kg');
    }

    /**
     * The average weight (g) the pond's fish were stocked at.
     *
     * Uses the earliest stocking's `avg_weight_g`. When several early stockings
     * carry a weight, the weighted average of the earliest date is used so a
     * mixed stocking is not misrepresented by a single batch. Returns 0.0 when
     * no stocking recorded a weight — the calculator turns that into no_growth.
     */
    public function startingAvgWeightG(Pond $pond): float
    {
        $earliest = FishStocking::query()
            ->where('pond_id', $pond->getKey())
            ->whereNotNull('avg_weight_g')
            ->orderBy('stocked_on')
            ->orderBy('id')
            ->first();

        if ($earliest === null) {
            return 0.0;
        }

        $rows = FishStocking::query()
            ->where('pond_id', $pond->getKey())
            ->whereDate('stocked_on', $earliest->stocked_on)
            ->whereNotNull('avg_weight_g')
            ->get(['quantity', 'avg_weight_g']);

        $totalFish = (int) $rows->sum('quantity');

        if ($totalFish === 0) {
            return (float) $earliest->avg_weight_g;
        }

        $weighted = $rows->sum(fn(FishStocking $s) => (float) $s->avg_weight_g * (int) $s->quantity);

        return round($weighted / $totalFish, 2);
    }

    /**
     * The latest sampled average weight (g) for the pond, or 0.0 when never
     * sampled. A missing sample is meaningful: weight gain cannot be established,
     * so the calculator reports no_growth rather than inventing a figure.
     */
    public function currentAvgWeightG(Pond $pond): float
    {
        return $pond->latestAvgWeightG() ?? 0.0;
    }

    /** The pond's live stock — the same figure every other module uses. */
    public function stockCount(Pond $pond): int
    {
        return app(\App\Services\Fish\FishStockService::class)->currentStock($pond);
    }

    /**
     * The full set of inputs behind a pond's FCR, so a report or details page can
     * show HOW the number was reached without recomputing anything.
     *
     * @return array{feed_consumed_kg: float, starting_avg_weight_g: float, current_avg_weight_g: float, stock_count: int, weight_gain_kg: float, avg_weight_per_fish_g: ?float}
     */
    public function inputs(Pond $pond): array
    {
        $feedKg = $this->feedConsumedKg($pond);
        $starting = $this->startingAvgWeightG($pond);
        $current = $this->currentAvgWeightG($pond);
        $stock = $this->stockCount($pond);

        $weightGainKg = $stock > 0 ? round((($current - $starting) * $stock) / 1000, 3) : 0.0;

        return [
            'feed_consumed_kg' => round($feedKg, 3),
            'starting_avg_weight_g' => round($starting, 2),
            'current_avg_weight_g' => round($current, 2),
            'stock_count' => $stock,
            'weight_gain_kg' => $weightGainKg,
            'avg_weight_per_fish_g' => $current > 0 ? round($current, 2) : null,
        ];
    }

    /**
     * Feed-conversion figures for several ponds at once, for a comparison table.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, array{result: FcrResult, inputs: array<string, mixed>}>
     */
    public function comparisonForPonds(array $pondIds): array
    {
        $out = [];

        foreach ($pondIds as $id) {
            $pond = Pond::find($id);

            if ($pond === null) {
                continue;
            }

            $out[$id] = [
                'result' => $this->forPond($pond),
                'inputs' => $this->inputs($pond),
            ];
        }

        return $out;
    }
}
