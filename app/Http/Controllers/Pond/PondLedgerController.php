<?php

namespace App\Http\Controllers\Pond;

use App\Http\Controllers\Controller;
use App\Models\Pond;
use App\Services\Pond\PondLedgerTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Pond Ledger — the complete transaction history of a pond.
 *
 * This is the reference page at /fish-farm/pond-ledger. The user selects a pond
 * and sees its FULL chronological timeline: stocking, mortality, transfer in and
 * transfer out (and, as their modules land, feed/sale/inspection/harvest).
 *
 * The timeline is assembled by PondLedgerTimelineService — the ONE place the
 * movement sources are combined. Nothing is computed in the view, and no figure
 * is invented: an empty pond shows an honest empty state.
 *
 * AUTHORIZATION: `permission:pond_ledger.view` middleware plus the policy.
 */
class PondLedgerController extends Controller
{
    public function __construct(
        private readonly PondLedgerTimelineService $timeline,
    ) {}

    public function __invoke(Request $request): \Inertia\Response
    {
        $ponds = Pond::query()
            ->orderBy('pond_number')
            ->get(['id', 'pond_number', 'name']);

        $selectedPondId = $request->query('pond');
        $selectedPond = $selectedPondId
            ? $ponds->firstWhere('id', (int) $selectedPondId)
            : null;

        // The transaction-type filter driven by the summary cards. Only keys that
        // exist in the catalogue are accepted, so an arbitrary query string cannot
        // introduce a filter the UI has no card for.
        $type = (string) $request->query('type', '');
        $type = array_key_exists($type, config('ledger.transaction_types', [])) ? $type : '';

        $timeline = null;
        $currentStock = null;
        $typeTotals = [];
        $timelinePayload = null;

        if ($selectedPond) {
            $timeline = $this->timeline->paginateForPondFiltered(
                $selectedPond,
                $type === '' ? [] : [$type],
                (int) config('fishfarm.pagination.default', 15),
            );
            $currentStock = $this->timeline->currentStock($selectedPond);
            $typeTotals = $this->timeline->typeTotals($selectedPond);

            $types = config('ledger.transaction_types', []);
            $timelinePayload = [
                'data' => collect($timeline->items())->map(function (array $row) use ($types): array {
                    $meta = $types[$row['type']] ?? [];

                    return [
                        'date' => optional($row['date'])->toDateString(),
                        'type' => $row['type'],
                        'type_short' => $meta['short'] ?? $row['type'],
                        'type_tone' => $meta['tone'] ?? 'default',
                        'description' => $row['description'],
                        'quantity' => $row['quantity'],
                        'money' => $row['money'],
                        'reference' => $row['reference'],
                        'user' => $row['user'],
                    ];
                })->all(),
                'current_page' => $timeline->currentPage(),
                'last_page' => $timeline->lastPage(),
                'total' => $timeline->total(),
                'from' => $timeline->firstItem(),
                'to' => $timeline->lastItem(),
                'links' => $timeline->linkCollection()->toArray(),
            ];
        }

        return \Inertia\Inertia::render('Ledger/Index', [
            'title' => 'Pond Ledger',
            'pondOptions' => $this->pondOptions($ponds)->all(),
            'selectedPond' => $selectedPond ? [
                'id' => $selectedPond->id,
                'pond_number' => $selectedPond->pond_number,
                'name' => $selectedPond->name,
            ] : null,
            'selectedPondId' => $selectedPondId ? (int) $selectedPondId : null,
            'timeline' => $timelinePayload,
            'currentStock' => $currentStock,
            'typeTotals' => $typeTotals,
            'selectedType' => $type,
            'typeOptions' => config('ledger.transaction_types', []),
        ]);
    }

    /**
     * Ponds selectable in the dropdown: id => "P-01 — Pond name".
     *
     * @param  Collection<int, Pond>  $ponds
     * @return Collection<int, string>
     */
    private function pondOptions(Collection $ponds): Collection
    {
        return $ponds->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"]);
    }
}
