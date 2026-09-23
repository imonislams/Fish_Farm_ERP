<?php

namespace App\Http\Controllers\Fish;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fish\StoreFishMortalityRequest;
use App\Models\FishMortality;
use App\Models\Pond;
use App\Services\Fish\FishStockService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Fish mortalities (stock OUT).
 *
 * Thin controller: authorize, validate via FormRequest (which also pre-checks
 * the non-negative stock rule), delegate the write to FishStockService, redirect.
 *
 * AUTHORIZATION: `permission:fish.mortality` middleware plus the policy check.
 */
class FishMortalityController extends Controller
{
    public function __construct(
        private readonly FishStockService $stockService,
    ) {}

    /** Paginated, filterable mortality list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');
        $from = $request->query('from');
        $to = $request->query('to');

        $mortalities = FishMortality::query()
            ->with('pond:id,name,pond_number')            // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($from, fn($q) => $q->whereDate('recorded_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('recorded_on', '<=', $to))
            ->orderByDesc('recorded_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Fish/Mortalities/Index', [
            'title' => 'Mortality',
            'rows' => [
                'data' => collect($mortalities->items())->map(fn (FishMortality $m): array => [
                    'id' => $m->id,
                    'date' => $m->recorded_on?->format('Y-m-d'),
                    'pond' => $m->pond?->name,
                    'pond_number' => $m->pond?->pond_number,
                    'quantity' => $m->quantity,
                    'avg_weight' => $m->avg_weight_g !== null ? (float) $m->avg_weight_g . ' g' : '—',
                    'cause' => $m->causeLabel(),
                    'urls' => [
                        'pond' => $m->pond ? route('ponds.show', $m->pond, absolute: false) : null,
                        'destroy' => route('fish.mortalities.destroy', $m, absolute: false),
                    ],
                ])->all(),
                'current_page' => $mortalities->currentPage(),
                'last_page' => $mortalities->lastPage(),
                'total' => $mortalities->total(),
                'from' => $mortalities->firstItem(),
                'to' => $mortalities->lastItem(),
                'links' => $mortalities->linkCollection()->toArray(),
            ],
            'filters' => ['pond' => $pondId, 'from' => $from, 'to' => $to],
            'options' => ['pondOptions' => $this->pondOptions()->all()],
        ]);
    }

    /** Show the mortality form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', FishMortality::class);

        // Live stock per pond, shown as a hint next to the quantity field.
        $stockByPond = $this->stockService->stockForPonds(
            Pond::query()->pluck('id')->all()
        );

        return \Inertia\Inertia::render('Fish/Mortalities/Create', [
            'title' => 'Record Mortality',
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'causeOptions' => collect(config('fish.mortality_causes', []))
                    ->map(fn(array $meta): string => $meta['label'])
                    ->all(),
                'stockByPond' => $stockByPond,
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a mortality. */
    public function store(StoreFishMortalityRequest $request): RedirectResponse
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
            ->route('fish.mortalities.index')
            ->with('success', "Recorded {$mortality->quantity} mortality in \"{$mortality->pond->name}\".");
    }

    /** Delete a mortality record (restores the stock it removed). */
    public function destroy(Request $request, FishMortality $mortality): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $mortality);

        $this->stockService->deleteMovement($mortality);

        return AsyncResponse::ok($request, 'Mortality record deleted.', 'fish.mortalities.index');
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

    /** Export the (filtered) mortality list as CSV. */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pondId = $request->query('pond');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = FishMortality::query()
            ->with('pond:id,name,pond_number')
            ->when($pondId, fn ($q) => $q->where('pond_id', $pondId))
            ->when($from, fn ($q) => $q->whereDate('recorded_on', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('recorded_on', '<=', $to))
            ->orderByDesc('recorded_on')->orderByDesc('id')
            ->get()
            ->map(fn (FishMortality $m): array => [
                $m->recorded_on?->format('Y-m-d') ?? '',
                $m->pond?->pond_number ?? '',
                $m->pond?->name ?? '',
                $m->quantity,
                $m->avg_weight_g ?? '',
                $m->causeLabel(),
                $m->creator?->name ?? '',
            ]);

        return CsvExporter::download('fish-mortality', [
            'Date', 'Pond No', 'Pond', 'Quantity', 'Avg Weight (g)', 'Cause', 'Recorded By',
        ], $rows);
    }}
