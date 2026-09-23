<?php

namespace App\Http\Controllers\Pond;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pond\StoreStockingRequest;
use App\Models\FishSpecies;
use App\Models\FishStocking;
use App\Models\Pond;
use App\Services\Fish\FishStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Pond Ledger — Stocking section ("Stock up on new fry").
 *
 * The New Stock form and the Stock History list. Both write/read the SAME
 * `fish_stockings` table as the Fish Stock module, through the SAME service
 * (FishStockService), so the two modules can never disagree about stock
 * (docs/BUSINESS_LOGIC.md §2 — one definition, one write path).
 *
 * AUTHORIZATION: `permission:pond_ledger.stocking.*` middleware plus the policy.
 */
class StockingController extends Controller
{
    public function __construct(
        private readonly FishStockService $stockService,
    ) {}

    /** The Stocking page: New Stock form + Stock History (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');

        $stockings = FishStocking::query()
            ->with(['pond:id,name,pond_number', 'species:id,name'])     // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->orderByDesc('stocked_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Ledger/Stocking', [
            'title' => 'Stocking',
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'speciesOptions' => $this->speciesOptions()->all(),
            ],
            'stockings' => [
                'data' => collect($stockings->items())->map(fn (FishStocking $s): array => [
                    'id' => $s->id,
                    'reference' => $s->reference,
                    'pond' => $s->pond?->name,
                    'pond_number' => $s->pond?->pond_number,
                    'species' => $s->species?->name,
                    'date' => $s->stocked_on?->toDateString(),
                    'quantity' => number_format((int) $s->quantity),
                ])->all(),
                'current_page' => $stockings->currentPage(),
                'last_page' => $stockings->lastPage(),
                'total' => $stockings->total(),
                'from' => $stockings->firstItem(),
                'to' => $stockings->lastItem(),
                'links' => $stockings->linkCollection()->toArray(),
            ],
            'filters' => ['pond' => $pondId],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a new stock record. */
    public function store(StoreStockingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->stockService->actorId();

        $stocking = $this->stockService->recordStocking($data);

        return redirect()
            ->route('ledger.stocking')
            ->with('success', "Stocked {$stocking->quantity} fish into \"{$stocking->pond->name}\".");
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

    /**
     * Active species selectable in the form: id => name.
     *
     * @return Collection<int, string>
     */
    private function speciesOptions(): Collection
    {
        return FishSpecies::query()
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
