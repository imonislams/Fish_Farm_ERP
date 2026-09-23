<?php

namespace App\Http\Controllers\Pond;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pond\StoreMortalityRequest;
use App\Models\FishMortality;
use App\Models\Pond;
use App\Services\Fish\FishStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Pond Ledger — Death (Mortality) section ("Fish mortality records").
 *
 * The New death records form and the Death History list. Both operate on the
 * SAME `fish_mortalities` table as the Fish Stock module, through the SAME
 * service (FishStockService), so the two modules can never disagree about stock.
 *
 * AUTHORIZATION: `permission:pond_ledger.mortality.*` middleware plus the policy.
 */
class MortalityController extends Controller
{
    public function __construct(
        private readonly FishStockService $stockService,
    ) {}

    /** The Mortality page: New death form + Death History (paginated). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');

        $mortalities = FishMortality::query()
            ->with('pond:id,name,pond_number')                        // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->orderByDesc('recorded_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Ledger/Mortality', [
            'title' => 'Death (Mortality)',
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'causeOptions' => collect(config('fish.mortality_causes', []))
                    ->map(fn(array $meta): string => $meta['label'])
                    ->all(),
            ],
            'mortalities' => [
                'data' => collect($mortalities->items())->map(fn (FishMortality $m): array => [
                    'id' => $m->id,
                    'date' => $m->recorded_on?->toDateString(),
                    'pond' => $m->pond?->name,
                    'pond_number' => $m->pond?->pond_number,
                    'quantity' => number_format((int) $m->quantity),
                    'cause' => $m->causeLabel(),
                ])->all(),
                'current_page' => $mortalities->currentPage(),
                'last_page' => $mortalities->lastPage(),
                'total' => $mortalities->total(),
                'from' => $mortalities->firstItem(),
                'to' => $mortalities->lastItem(),
                'links' => $mortalities->linkCollection()->toArray(),
            ],
            'filters' => ['pond' => $pondId],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a mortality record. */
    public function store(StoreMortalityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->stockService->actorId();

        try {
            $mortality = $this->stockService->recordMortality($data);
        } catch (\DomainException $e) {
            // The service guard is authoritative even if the form was bypassed.
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('ledger.mortality')
            ->with('success', "Recorded {$mortality->quantity} mortality in \"{$mortality->pond->name}\".");
    }

    /**
     * Ponds selectable in the form: id => "P-01 — Pond name".
     *
     * @return Collection<int, string>
     */
    private function pondOptions(): Collection
    {
        return Pond::query()
            ->orderBy('pond_number')
            ->get(['id', 'pond_number', 'name'])
            ->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"]);
    }
}
