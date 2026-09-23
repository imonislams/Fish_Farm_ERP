<?php

namespace App\Http\Controllers\Fish;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fish\StoreHarvestRequest;
use App\Models\FishSpecies;
use App\Models\Harvest;
use App\Models\Pond;
use App\Services\Fish\FishStockService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Harvests (stock OUT).
 *
 * Thin controller: authorize, validate via FormRequest (which also pre-checks
 * the non-negative stock rule), delegate the write to FishStockService, redirect.
 *
 * AUTHORIZATION: `permission:fish.harvest` middleware plus the policy check.
 */
class HarvestController extends Controller
{
    public function __construct(
        private readonly FishStockService $stockService,
    ) {}

    /** Paginated, filterable harvest list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');
        $speciesId = $request->query('species');
        $from = $request->query('from');
        $to = $request->query('to');

        $harvests = Harvest::query()
            ->with(['pond:id,name,pond_number', 'species:id,name'])   // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($speciesId, fn($q) => $q->where('fish_species_id', $speciesId))
            ->when($from, fn($q) => $q->whereDate('harvested_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('harvested_on', '<=', $to))
            ->orderByDesc('harvested_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Fish/Harvests/Index', [
            'title' => 'Harvest',
            'rows' => [
                'data' => collect($harvests->items())->map(fn (Harvest $h): array => [
                    'id' => $h->id,
                    'date' => $h->harvested_on?->format('Y-m-d'),
                    'pond' => $h->pond?->name,
                    'pond_number' => $h->pond?->pond_number,
                    'species' => $h->species?->name,
                    'quantity' => $h->quantity,
                    'total_weight' => $h->total_weight_kg !== null ? (float) $h->total_weight_kg . ' kg' : '—',
                    'destination' => $h->destination,
                    'urls' => [
                        'pond' => $h->pond ? route('ponds.show', $h->pond, absolute: false) : null,
                        'destroy' => route('fish.harvests.destroy', $h, absolute: false),
                    ],
                ])->all(),
                'current_page' => $harvests->currentPage(),
                'last_page' => $harvests->lastPage(),
                'total' => $harvests->total(),
                'from' => $harvests->firstItem(),
                'to' => $harvests->lastItem(),
                'links' => $harvests->linkCollection()->toArray(),
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

    /** Show the harvest form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', Harvest::class);

        return \Inertia\Inertia::render('Fish/Harvests/Create', [
            'title' => 'Record Harvest',
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'speciesOptions' => $this->speciesOptions()->all(),
                'stockByPond' => $this->stockService->stockForPonds(
                    Pond::query()->pluck('id')->all()
                ),
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a harvest. */
    public function store(StoreHarvestRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->stockService->actorId();

        try {
            $harvest = $this->stockService->recordHarvest($data);
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('fish.harvests.index')
            ->with('success', "Harvested {$harvest->quantity} fish from \"{$harvest->pond->name}\".");
    }

    /** Delete a harvest record (restores the stock it removed). */
    public function destroy(Request $request, Harvest $harvest): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $harvest);

        $this->stockService->deleteMovement($harvest);

        return AsyncResponse::ok($request, 'Harvest record deleted.', 'fish.harvests.index');
    }

    /**
     * Ponds selectable in a form/filter: id => "P-01 — Pond name".
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
     * Species selectable in a form/filter: id => name (active only).
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

    /** Export the (filtered) harvest list as CSV. */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pondId = $request->query('pond');
        $speciesId = $request->query('species');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = Harvest::query()
            ->with(['pond:id,name,pond_number', 'species:id,name'])
            ->when($pondId, fn ($q) => $q->where('pond_id', $pondId))
            ->when($speciesId, fn ($q) => $q->where('fish_species_id', $speciesId))
            ->when($from, fn ($q) => $q->whereDate('harvested_on', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('harvested_on', '<=', $to))
            ->orderByDesc('harvested_on')->orderByDesc('id')
            ->get()
            ->map(fn (Harvest $h): array => [
                $h->harvested_on?->format('Y-m-d') ?? '',
                $h->pond?->pond_number ?? '',
                $h->pond?->name ?? '',
                $h->species?->name ?? '',
                $h->quantity,
                $h->total_weight_kg ?? '',
                $h->destination ?? '',
                $h->creator?->name ?? '',
            ]);

        return CsvExporter::download('harvests', [
            'Date', 'Pond No', 'Pond', 'Species', 'Quantity', 'Total Weight (kg)', 'Destination', 'Recorded By',
        ], $rows);
    }}
