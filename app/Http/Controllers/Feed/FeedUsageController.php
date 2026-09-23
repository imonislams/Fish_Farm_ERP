<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feed\StoreFeedUsageRequest;
use App\Models\FeedType;
use App\Models\FeedUsage;
use App\Models\Pond;
use App\Services\Feed\FeedStockService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Feed usages (stock OUT, into a pond).
 *
 * Thin controller: authorize, validate via FormRequest (which also pre-checks
 * the non-negative stock rule), delegate the write to FeedStockService, redirect.
 *
 * AUTHORIZATION: `permission:feed.usage` middleware plus the policy check.
 */
class FeedUsageController extends Controller
{
    public function __construct(
        private readonly FeedStockService $stockService,
    ) {}

    /** Paginated, filterable usage list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');
        $typeId = $request->query('type');
        $from = $request->query('from');
        $to = $request->query('to');

        $usages = FeedUsage::query()
            ->with(['pond:id,name,pond_number', 'feedType:id,name,brand'])   // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($typeId, fn($q) => $q->where('feed_type_id', $typeId))
            ->when($from, fn($q) => $q->whereDate('used_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('used_on', '<=', $to))
            ->orderByDesc('used_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Feed/Usages/Index', [
            'title' => 'Food Usage',
            'rows' => [
                'data' => collect($usages->items())->map(fn (FeedUsage $u): array => [
                    'id' => $u->id,
                    'date' => $u->used_on?->format('Y-m-d'),
                    'pond' => $u->pond?->name,
                    'pond_number' => $u->pond?->pond_number,
                    'feed' => $u->feedType?->name,
                    'quantity' => $u->quantity_kg !== null ? (float) $u->quantity_kg . ' kg' : '—',
                    'urls' => [
                        'pond' => $u->pond ? route('ponds.show', $u->pond, absolute: false) : null,
                        'destroy' => route('feed.usages.destroy', $u, absolute: false),
                    ],
                ])->all(),
                'current_page' => $usages->currentPage(),
                'last_page' => $usages->lastPage(),
                'total' => $usages->total(),
                'from' => $usages->firstItem(),
                'to' => $usages->lastItem(),
                'links' => $usages->linkCollection()->toArray(),
            ],
            'filters' => ['pond' => $pondId, 'type' => $typeId, 'from' => $from, 'to' => $to],
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'typeOptions' => $this->typeOptions()->all(),
            ],
        ]);
    }

    /** Show the usage form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', FeedUsage::class);

        return \Inertia\Inertia::render('Feed/Usages/Create', [
            'title' => 'Record Food Usage',
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'typeOptions' => $this->typeOptions()->all(),
                // Current stock per feed type, shown as a hint next to the quantity.
                'stockByType' => $this->stockService->stockForTypes(
                    FeedType::query()->pluck('id')->all()
                ),
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a usage. */
    public function store(StoreFeedUsageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->stockService->actorId();

        try {
            $usage = $this->stockService->recordUsage($data);
        } catch (\DomainException $e) {
            // The service guard is authoritative even if the form was bypassed.
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('feed.usages.index')
            ->with('success', "Used {$usage->quantity_kg} kg in \"{$usage->pond->name}\".");
    }

    /** Delete a usage record (restores the stock it removed). */
    public function destroy(Request $request, FeedUsage $usage): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $usage);

        $this->stockService->deleteOutMovement($usage);

        return AsyncResponse::ok($request, 'Usage record deleted.', 'feed.usages.index');
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
     * Feed types selectable in a form/filter: id => "Name (Brand)".
     *
     * @return Collection<int, string>
     */
    private function typeOptions(): Collection
    {
        return FeedType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'brand'])
            ->mapWithKeys(fn(FeedType $t) => [$t->id => $t->displayName()]);
    }

    /** Export the (filtered) feed usage list as CSV. */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pondId = $request->query('pond');
        $typeId = $request->query('type');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = FeedUsage::query()
            ->with(['pond:id,name,pond_number', 'feedType:id,name'])
            ->when($pondId, fn ($q) => $q->where('pond_id', $pondId))
            ->when($typeId, fn ($q) => $q->where('feed_type_id', $typeId))
            ->when($from, fn ($q) => $q->whereDate('used_on', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('used_on', '<=', $to))
            ->orderByDesc('used_on')->orderByDesc('id')
            ->get()
            ->map(fn (FeedUsage $u): array => [
                $u->used_on?->format('Y-m-d') ?? '',
                $u->pond?->pond_number ?? '',
                $u->pond?->name ?? '',
                $u->feedType?->name ?? '',
                $u->quantity_kg,
                $u->creator?->name ?? '',
            ]);

        return CsvExporter::download('feed-usage', [
            'Date', 'Pond No', 'Pond', 'Feed', 'Quantity (kg)', 'Recorded By',
        ], $rows);
    }}
