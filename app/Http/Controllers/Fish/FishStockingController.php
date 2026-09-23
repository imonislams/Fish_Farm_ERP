<?php

namespace App\Http\Controllers\Fish;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fish\StoreFishStockingRequest;
use App\Models\FishSpecies;
use App\Models\FishStocking;
use App\Models\Pond;
use App\Services\Fish\FishStockService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
/**
 * Fish stockings (stock IN).
 *
 * Thin controller: authorize, validate via FormRequest, delegate the write to
 * FishStockService (which derives weights inside a transaction), redirect.
 *
 * AUTHORIZATION: `permission:fish.*` middleware on every route plus the policy
 * check inside each action.
 */
class FishStockingController extends Controller
{
    public function __construct(
        private readonly FishStockService $stockService,
    ) {}

    /** Paginated, filterable stocking list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');
        $speciesId = $request->query('species');
        $from = $request->query('from');
        $to = $request->query('to');

        $stockings = FishStocking::query()
            ->with(['pond:id,name,pond_number', 'species:id,name'])   // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($speciesId, fn($q) => $q->where('fish_species_id', $speciesId))
            ->when($from, fn($q) => $q->whereDate('stocked_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('stocked_on', '<=', $to))
            ->orderByDesc('stocked_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Fish/Stockings/Index', [
            'title' => 'Stock In / Stocking',
            'rows' => [
                'data' => collect($stockings->items())->map(fn (FishStocking $s): array => [
                    'id' => $s->id,
                    'date' => $s->stocked_on?->format('Y-m-d'),
                    'pond' => $s->pond?->name,
                    'pond_number' => $s->pond?->pond_number,
                    'species' => $s->species?->name,
                    'quantity' => $s->quantity,
                    'avg_weight' => $s->avgWeightDisplay(),
                    'total_weight' => $s->weightDisplay(),
                    'urls' => [
                        'pond' => $s->pond ? route('ponds.show', $s->pond, absolute: false) : null,
                        'destroy' => route('fish.stockings.destroy', $s, absolute: false),
                    ],
                ])->all(),
                'current_page' => $stockings->currentPage(),
                'last_page' => $stockings->lastPage(),
                'total' => $stockings->total(),
                'from' => $stockings->firstItem(),
                'to' => $stockings->lastItem(),
                'links' => $stockings->linkCollection()->toArray(),
            ],
            'filters' => [
                'pond' => $pondId, 'species' => $speciesId,
                'from' => $from, 'to' => $to,
            ],
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'speciesOptions' => $this->speciesOptions()->all(),
            ],
        ]);
    }

    /** Show the stocking form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', FishStocking::class);

        return \Inertia\Inertia::render('Fish/Stockings/Create', [
            'title' => 'Record Stocking',
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'speciesOptions' => $this->speciesOptions()->all(),
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a stocking. */
    public function store(StoreFishStockingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->stockService->actorId();

        $stocking = $this->stockService->recordStocking($data);

        return redirect()
            ->route('fish.stockings.index')
            ->with('success', "Stocked {$stocking->quantity} fish into \"{$stocking->pond->name}\".");
    }

    /**
     * Delete a stocking (removes stock).
     *
     * Refused when the fish it added have already been harvested or have died —
     * the service raises a DomainException turned into a flash error here.
     */
    public function destroy(Request $request, FishStocking $stocking): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $stocking);

        try {
            $this->stockService->deleteStocking($stocking);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, 'Stocking record deleted.', 'fish.stockings.index');
    }

    /**
     * Ponds selectable in a form/filter: id => "P-01 — Pond name".
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function pondOptions(): \Illuminate\Support\Collection
    {
        return Pond::query()
            ->orderBy('pond_number')
            ->get(['id', 'pond_number', 'name'])
            ->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"]);
    }

    /**
     * Species selectable in a form/filter: id => name (active only, by default).
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function speciesOptions(): \Illuminate\Support\Collection
    {
        return FishSpecies::query()
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Export the (filtered) stocking list as CSV — real rows only.
     * Streamed, UTF-8 with BOM (docs/ERP-UI-UX.md "CSV export").
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pondId = $request->query('pond');
        $speciesId = $request->query('species');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = FishStocking::query()
            ->with(['pond:id,name,pond_number', 'species:id,name'])
            ->when($pondId, fn ($q) => $q->where('pond_id', $pondId))
            ->when($speciesId, fn ($q) => $q->where('fish_species_id', $speciesId))
            ->when($from, fn ($q) => $q->whereDate('stocked_on', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('stocked_on', '<=', $to))
            ->orderByDesc('stocked_on')->orderByDesc('id')
            ->get()
            ->map(fn (FishStocking $s): array => [
                $s->stocked_on?->format('Y-m-d') ?? '',
                $s->pond?->pond_number ?? '',
                $s->pond?->name ?? '',
                $s->species?->name ?? '',
                $s->quantity,
                $s->avg_weight_g ?? '',
                $s->total_weight_kg ?? '',
                $s->supplier_name ?? '',
                $s->creator?->name ?? '',
            ]);

        return CsvExporter::download('fish-stockings', [
            'Date', 'Pond No', 'Pond', 'Species', 'Quantity', 'Avg Weight (g)', 'Total Weight (kg)', 'Supplier', 'Recorded By',
        ], $rows);
    }}
