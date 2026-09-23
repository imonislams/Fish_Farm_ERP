<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feed\StoreFeedPurchaseRequest;
use App\Models\FeedPurchase;
use App\Models\FeedType;
use App\Services\Feed\FeedStockService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Feed purchases (stock IN).
 *
 * Thin controller: authorize, validate via FormRequest, delegate the write to
 * FeedStockService (which derives the line cost inside a transaction), redirect.
 *
 * AUTHORIZATION: `permission:feed.view` (list) / `feed.purchase` (write) plus the
 * policy check inside each action.
 */
class FeedPurchaseController extends Controller
{
    public function __construct(
        private readonly FeedStockService $stockService,
    ) {}

    /** Paginated, filterable purchase list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $typeId = $request->query('type');
        $from = $request->query('from');
        $to = $request->query('to');

        $purchases = FeedPurchase::query()
            ->with('feedType:id,name,brand')                  // avoids N+1
            ->when($typeId, fn($q) => $q->where('feed_type_id', $typeId))
            ->when($from, fn($q) => $q->whereDate('purchased_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('purchased_on', '<=', $to))
            ->orderByDesc('purchased_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Feed/Purchases/Index', [
            'title' => 'Food Purchase',
            'rows' => [
                'data' => collect($purchases->items())->map(fn (FeedPurchase $p): array => [
                    'id' => $p->id,
                    'date' => $p->purchased_on?->format('Y-m-d'),
                    'feed' => $p->feedType?->name,
                    'brand' => $p->feedType?->brand,
                    'supplier' => $p->supplier_name,
                    'quantity' => $p->quantityDisplay(),
                    'unit_cost' => $p->unit_cost !== null ? (float) $p->unit_cost : null,
                    'total_cost' => $p->total_cost !== null ? (float) $p->total_cost : null,
                    'urls' => [
                        'destroy' => route('feed.purchases.destroy', $p, absolute: false),
                    ],
                ])->all(),
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'total' => $purchases->total(),
                'from' => $purchases->firstItem(),
                'to' => $purchases->lastItem(),
                'links' => $purchases->linkCollection()->toArray(),
            ],
            'filters' => ['type' => $typeId, 'from' => $from, 'to' => $to],
            'options' => ['typeOptions' => $this->typeOptions()->all()],
        ]);
    }

    /** Show the purchase form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', FeedPurchase::class);

        return \Inertia\Inertia::render('Feed/Purchases/Create', [
            'title' => 'Record Food Purchase',
            'options' => ['typeOptions' => $this->typeOptions()->all()],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a purchase. */
    public function store(StoreFeedPurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->stockService->actorId();

        $purchase = $this->stockService->recordPurchase($data);

        return redirect()
            ->route('feed.purchases.index')
            ->with('success', "Recorded {$purchase->quantity_kg} kg of \"{$purchase->feedType->name}\".");
    }

    /**
     * Delete a purchase (removes stock).
     *
     * Refused when the feed it added has already been used or adjusted away —
     * the service raises a DomainException turned into a flash error here.
     */
    public function destroy(Request $request, FeedPurchase $purchase): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $purchase);

        try {
            $this->stockService->deletePurchase($purchase);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, 'Purchase record deleted.', 'feed.purchases.index');
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

    /** Export the (filtered) feed purchase list as CSV. */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $typeId = $request->query('type');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = FeedPurchase::query()
            ->with('feedType:id,name,brand')
            ->when($typeId, fn ($q) => $q->where('feed_type_id', $typeId))
            ->when($from, fn ($q) => $q->whereDate('purchased_on', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('purchased_on', '<=', $to))
            ->orderByDesc('purchased_on')->orderByDesc('id')
            ->get()
            ->map(fn (FeedPurchase $p): array => [
                $p->purchased_on?->format('Y-m-d') ?? '',
                $p->feedType?->name ?? '',
                $p->feedType?->brand ?? '',
                $p->quantity_kg,
                $p->unit_cost ?? '',
                $p->total_cost ?? '',
                $p->supplier_name ?? '',
                $p->invoice_no ?? '',
                $p->creator?->name ?? '',
            ]);

        return CsvExporter::download('feed-purchases', [
            'Date', 'Feed', 'Brand', 'Quantity (kg)', 'Unit Cost', 'Total Cost', 'Supplier', 'Invoice', 'Recorded By',
        ], $rows);
    }}
