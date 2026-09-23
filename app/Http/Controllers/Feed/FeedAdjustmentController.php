<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feed\StoreFeedAdjustmentRequest;
use App\Models\FeedStockAdjustment;
use App\Models\FeedType;
use App\Services\Feed\FeedStockService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Manual feed stock adjustments.
 *
 * Thin controller: authorize, validate via FormRequest (which requires a reason
 * and pre-checks an OUT adjustment against available stock), delegate the write
 * to FeedStockService, redirect.
 *
 * AUTHORIZATION: `permission:feed.adjust` middleware plus the policy check.
 */
class FeedAdjustmentController extends Controller
{
    public function __construct(
        private readonly FeedStockService $stockService,
    ) {}

    /** Paginated, filterable adjustment list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $typeId = $request->query('type');
        $from = $request->query('from');
        $to = $request->query('to');

        $adjustments = FeedStockAdjustment::query()
            ->with('feedType:id,name,brand')                  // avoids N+1
            ->when($typeId, fn($q) => $q->where('feed_type_id', $typeId))
            ->when($from, fn($q) => $q->whereDate('adjusted_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('adjusted_on', '<=', $to))
            ->orderByDesc('adjusted_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Feed/Adjustments/Index', [
            'title' => 'Food Stock Adjustment',
            'rows' => [
                'data' => collect($adjustments->items())->map(fn (FeedStockAdjustment $a): array => [
                    'id' => $a->id,
                    'date' => $a->adjusted_on?->format('Y-m-d'),
                    'feed' => $a->feedType?->name,
                    'direction' => $a->directionLabel(),
                    'is_increase' => $a->isIncrease(),
                    'quantity' => $a->signedQuantityDisplay(),
                    'reason' => $a->reasonLabel(),
                    'urls' => [
                        'destroy' => route('feed.adjustments.destroy', $a, absolute: false),
                    ],
                ])->all(),
                'current_page' => $adjustments->currentPage(),
                'last_page' => $adjustments->lastPage(),
                'total' => $adjustments->total(),
                'from' => $adjustments->firstItem(),
                'to' => $adjustments->lastItem(),
                'links' => $adjustments->linkCollection()->toArray(),
            ],
            'filters' => ['type' => $typeId, 'from' => $from, 'to' => $to],
            'options' => ['typeOptions' => $this->typeOptions()->all()],
        ]);
    }

    /** Show the adjustment form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', FeedStockAdjustment::class);

        return \Inertia\Inertia::render('Feed/Adjustments/Create', [
            'title' => 'Adjust Food Stock',
            'options' => [
                'typeOptions' => $this->typeOptions()->all(),
                'directionOptions' => collect(config('feed.adjustment_directions', []))
                    ->map(fn(array $meta): string => $meta['label'])
                    ->all(),
                'reasonOptions' => collect(config('feed.adjustment_reasons', []))
                    ->map(fn(array $meta): string => $meta['label'])
                    ->all(),
                // Current stock per feed type, shown so the user can see the limit.
                'stockByType' => $this->stockService->stockForTypes(
                    FeedType::query()->pluck('id')->all()
                ),
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist an adjustment. */
    public function store(StoreFeedAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->stockService->actorId();

        try {
            $adjustment = $this->stockService->recordAdjustment($data);
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $direction = $adjustment->isIncrease() ? 'increased' : 'decreased';

        return redirect()
            ->route('feed.adjustments.index')
            ->with('success', "Stock {$direction} by {$adjustment->quantity_kg} kg for \"{$adjustment->feedType->name}\".");
    }

    /** Delete an adjustment. */
    public function destroy(Request $request, FeedStockAdjustment $adjustment): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $adjustment);

        try {
            $this->stockService->deleteAdjustment($adjustment);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, 'Adjustment record deleted.', 'feed.adjustments.index');
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
}
