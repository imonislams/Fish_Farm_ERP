<?php

namespace App\Services\Pond;

use App\Models\FishMortality;
use App\Models\FishStocking;
use App\Models\Pond;
use App\Models\PondTransfer;
use App\Services\Fish\FishStockService;

/**
 * Pond ledger TIMELINE — the complete pond transaction/history system.
 *
 * A pond's ledger is the CHRONOLOGICAL union of every movement that touched it,
 * not a single table:
 *
 *   Stocking       (fish_stockings)      → stock IN
 *   Mortality      (fish_mortalities)    → stock OUT
 *   Transfer in    (pond_transfers.to)   → stock IN
 *   Transfer out   (pond_transfers.from) → stock OUT
 *
 * and, when their modules land:
 *
 *   Feed           (feed_usages)
 *   Sale           (sales)
 *   Inspection     (inspections)
 *   Harvest        (harvests)
 *
 * EXTENSIBILITY (docs/ARCHITECTURE.md §10): a future module contributes to the
 * ledger by adding ONE row type to `buildRows()`. Nothing else changes — the
 * view only ever renders the normalised row shape produced here, so a new
 * source type never requires touching the Blade template.
 *
 * NORMALISED ROW SHAPE (every producer must return this):
 *   [
 *     'date'        => \Illuminate\Support\Carbon,
 *     'type'        => string,   // stocking | mortality | transfer_in | transfer_out | …
 *     'description' => string,
 *     'quantity'    => ?string,  // display string ("205 kg", "-75 pcs") or null
 *     'money'       => ?string,  // display string ("৳88,920.00") or null
 *     'reference'   => ?string,
 *     'user'        => string,
 *     'sort'        => int,      // stable tie-breaker (id)
 *   ]
 *
 * No business figure is computed in a view; the money/quantity display strings
 * are produced here so the template stays presentation-only.
 */
class PondLedgerTimelineService
{
    public function __construct(
        private readonly FishStockService $stockService,
    ) {}

    /**
     * The full chronological timeline for a pond, newest first.
     *
     * Returns a Collection of normalised rows. Callers paginate or slice it.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function timelineForPond(Pond $pond): \Illuminate\Support\Collection
    {
        $pondId = $pond->getKey();

        $rows = collect()
            ->merge($this->stockingRows($pondId))
            ->merge($this->mortalityRows($pondId))
            ->merge($this->transferRows($pondId));

        // Newest first; ties broken by the row's own id so ordering is stable.
        return $rows
            ->sortByDesc(fn(array $row) => [$row['date']->timestamp, $row['sort']])
            ->values();
    }

    /**
     * Paginate the timeline for a pond.
     *
     * The merged collection is paginated in memory because the timeline spans
     * several tables; the per-pond slice is small enough for this to be correct
     * and cheap (only one pond's history is ever loaded).
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForPond(Pond $pond, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $rows = $this->timelineForPond($pond);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($this->currentPage(), $perPage)->values(),
            $rows->count(),
            $perPage,
            $this->currentPage(),
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()],
        );
    }

    /** Live stock for the pond — the ONE definition, from FishStockService. */
    public function currentStock(Pond $pond): int
    {
        return $this->stockService->currentStock($pond);
    }

    /**
     * Paginate the timeline for a pond, optionally restricted to one or more
     * transaction types.
     *
     * The filter is applied BEFORE pagination, so a filtered view paginates the
     * filtered set — never a page of everything with rows hidden afterwards.
     *
     * @param  array<int, string>  $types  type keys to include; empty = all
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForPondFiltered(Pond $pond, array $types = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $rows = $this->timelineForPond($pond);

        if ($types !== []) {
            $rows = $rows->whereIn('type', $types)->values();
        }

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($this->currentPage(), $perPage)->values(),
            $rows->count(),
            $perPage,
            $this->currentPage(),
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()],
        );
    }

    /**
     * Real per-type totals for a pond's timeline — the counts the summary cards
     * display and filter by.
     *
     * Every type in the catalogue is present, defaulting to zero, so the card row
     * is stable. These are genuine counts of the assembled rows — never estimated.
     *
     * @return array<string, array{count: int, money: float}>  type key => totals
     */
    public function typeTotals(Pond $pond): array
    {
        $rows = $this->timelineForPond($pond);

        $totals = [];

        foreach (array_keys(config('ledger.transaction_types', [])) as $type) {
            $totals[$type] = ['count' => 0, 'money' => 0.0];
        }

        foreach ($rows as $row) {
            $type = $row['type'];

            if (! isset($totals[$type])) {
                $totals[$type] = ['count' => 0, 'money' => 0.0];
            }

            $totals[$type]['count']++;

            // Money is rendered as a display string (e.g. "1,200.00") by the
            // producers; sum the numeric value when it is present and numeric.
            $money = $row['money'] ?? null;

            if (is_string($money)) {
                $numeric = (float) preg_replace('/[^0-9.\-]/', '', $money);
                $totals[$type]['money'] += $numeric;
            } elseif (is_numeric($money)) {
                $totals[$type]['money'] += (float) $money;
            }
        }

        foreach ($totals as $type => $data) {
            $totals[$type]['money'] = round($data['money'], 2);
        }

        return $totals;
    }

    /* ---------------------------------------------------------------------
     | Producers — one method per movement source. Add a new source by adding a
     | method here and merging it in timelineForPond().
    |---------------------------------------------------------------------*/

    /**
     * Stocking rows (stock IN).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function stockingRows(int|string $pondId): \Illuminate\Support\Collection
    {
        return FishStocking::query()
            ->with(['species:id,name', 'creator:id,name'])
            ->where('pond_id', $pondId)
            ->get()
            ->map(fn(FishStocking $s): array => [
                'date' => $s->stocked_on ?? $s->created_at,
                'type' => 'stocking',
                'description' => 'Stocking' . ($s->species ? " — {$s->species->name}" : ''),
                'quantity' => '+' . number_format((int) $s->quantity) . ' pcs',
                'money' => null,
                'reference' => $s->reference ?? null,
                'user' => $s->creator?->name ?? 'System',
                'sort' => (int) $s->id,
            ]);
    }

    /**
     * Mortality rows (stock OUT).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mortalityRows(int|string $pondId): \Illuminate\Support\Collection
    {
        return FishMortality::query()
            ->with('creator:id,name')
            ->where('pond_id', $pondId)
            ->get()
            ->map(fn(FishMortality $m): array => [
                'date' => $m->recorded_on ?? $m->created_at,
                'type' => 'mortality',
                'description' => 'Death — ' . ($m->causeLabel() ?: 'unknown'),
                'quantity' => '-' . number_format((int) $m->quantity) . ' pcs',
                'money' => null,
                'reference' => $m->reference ?? null,
                'user' => $m->creator?->name ?? 'System',
                'sort' => (int) $m->id,
            ]);
    }

    /**
     * Transfer rows — a transfer produces TWO ledger rows: an OUT on the source
     * pond and an IN on the destination pond.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function transferRows(int|string $pondId): \Illuminate\Support\Collection
    {
        $transfers = PondTransfer::query()
            ->with(['fromPond:id,name,pond_number', 'toPond:id,name,pond_number', 'species:id,name', 'creator:id,name'])
            ->involvingPond($pondId)
            ->get();

        $rows = collect();

        foreach ($transfers as $transfer) {
            $species = $transfer->species?->name;
            $quantity = number_format((int) $transfer->quantity) . ' pcs';
            $user = $transfer->creator?->name ?? 'System';
            $date = $transfer->transferred_on ?? $transfer->created_at;

            // The OUT side (this pond is the source).
            if ((int) $transfer->from_pond_id === (int) $pondId) {
                $rows->push([
                    'date' => $date,
                    'type' => 'transfer_out',
                    'description' => 'Transfer out → ' . ($transfer->toPond?->name ?? 'pond')
                        . ($species ? " · {$species}" : ''),
                    'quantity' => '-' . $quantity,
                    'money' => null,
                    'reference' => $transfer->reference,
                    'user' => $user,
                    'sort' => (int) $transfer->id,
                ]);
            }

            // The IN side (this pond is the destination).
            if ((int) $transfer->to_pond_id === (int) $pondId) {
                $rows->push([
                    'date' => $date,
                    'type' => 'transfer_in',
                    'description' => 'Transfer in ← ' . ($transfer->fromPond?->name ?? 'pond')
                        . ($species ? " · {$species}" : ''),
                    'quantity' => '+' . $quantity,
                    'money' => null,
                    'reference' => $transfer->reference,
                    'user' => $user,
                    'sort' => (int) $transfer->id,
                ]);
            }
        }

        return $rows;
    }

    /** Resolve the current page from the request (used by the in-memory paginator). */
    private function currentPage(): int
    {
        return max(1, (int) \Illuminate\Pagination\Paginator::resolveCurrentPage());
    }
}
