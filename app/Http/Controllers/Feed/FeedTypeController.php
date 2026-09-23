<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feed\StoreFeedTypeRequest;
use App\Http\Requests\Feed\UpdateFeedTypeRequest;
use App\Models\FeedType;
use App\Services\Feed\FeedStockService;
use App\Services\Feed\FeedTypeService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Feed type management (master data).
 *
 * Thin controller: authorize, delegate to FeedTypeService, return a view or a
 * redirect with a flash message. The "type in use cannot be deleted" business
 * rule lives in the service — this controller only turns the resulting
 * DomainException into a clear flash error rather than a stack trace.
 *
 * AUTHORIZATION: `permission:feed.*` middleware on every route plus the policy
 * check inside each action.
 */
class FeedTypeController extends Controller
{
    public function __construct(
        private readonly FeedTypeService $typeService,
        private readonly FeedStockService $stockService,
    ) {}

    /** Paginated, searchable feed type list with current stock (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));

        $types = FeedType::query()
            ->withCount(['purchases', 'usages', 'adjustments'])   // avoids a query per row
            ->search($search)
            ->orderBy('name')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        // Current stock per type, computed once for the whole page.
        $stockByType = $this->stockService->stockForTypes($types->pluck('id')->all());

        return \Inertia\Inertia::render('Feed/Types/Index', [
            'title' => 'Food Types',
            'types' => [
                'data' => collect($types->items())->map(function (FeedType $type) use ($stockByType): array {
                    $stock = $stockByType[$type->id] ?? 0;
                    $isLow = $type->low_stock_level_kg !== null
                        && $stock <= (float) $type->low_stock_level_kg;
                    $isCritical = $this->stockService->isCritical(
                        $stock,
                        $type->critical_stock_level_kg !== null ? (float) $type->critical_stock_level_kg : null,
                    );

                    return [
                        'id' => $type->id,
                        'name' => $type->name,
                        'brand' => $type->brand,
                        'protein' => $type->protein_percent,
                        'stock' => $stock,
                        'is_low' => $isLow,
                        'is_critical' => $isCritical,
                        'is_active' => (bool) $type->is_active,
                        'records' => $type->purchases_count + $type->usages_count + $type->adjustments_count,
                        'urls' => [
                            'edit' => route('feed.types.edit', $type, absolute: false),
                            'destroy' => route('feed.types.destroy', $type, absolute: false),
                        ],
                    ];
                })->all(),
                'current_page' => $types->currentPage(),
                'last_page' => $types->lastPage(),
                'total' => $types->total(),
                'from' => $types->firstItem(),
                'to' => $types->lastItem(),
                'links' => $types->linkCollection()->toArray(),
            ],
            'filters' => ['search' => $search],
        ]);
    }

    /** Show the create form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', FeedType::class);

        return \Inertia\Inertia::render('Feed/Types/Create', [
            'title' => 'New Food Type',
        ]);
    }

    /** Persist a new feed type. */
    public function store(StoreFeedTypeRequest $request): RedirectResponse
    {
        $type = $this->typeService->create($request->validated());

        return redirect()
            ->route('feed.types.index')
            ->with('success', "Feed type \"{$type->name}\" created.");
    }

    /** Show the edit form (Inertia/React). */
    public function edit(FeedType $feedType): \Inertia\Response
    {
        Gate::authorize('update', $feedType);

        $feedType->loadCount(['purchases', 'usages', 'adjustments']);

        return \Inertia\Inertia::render('Feed/Types/Edit', [
            'title' => 'Edit — ' . $feedType->name,
            'type' => [
                'id' => $feedType->id,
                'name' => $feedType->name,
                'brand' => $feedType->brand,
                'protein_percent' => $feedType->protein_percent,
                'package_weight_kg' => $feedType->package_weight_kg,
                'unit' => $feedType->unit,
                'default_unit_cost' => $feedType->default_unit_cost,
                'low_stock_level_kg' => $feedType->low_stock_level_kg,
                'critical_stock_level_kg' => $feedType->critical_stock_level_kg,
                'description' => $feedType->description,
                'is_active' => (bool) $feedType->is_active,
                'purchases_count' => $feedType->purchases_count,
                'usages_count' => $feedType->usages_count,
                'adjustments_count' => $feedType->adjustments_count,
            ],
            'currentStockKg' => $this->stockService->currentStockKg($feedType),
        ]);
    }

    /** Persist changes. */
    public function update(UpdateFeedTypeRequest $request, FeedType $feedType): RedirectResponse
    {
        Gate::authorize('update', $feedType);

        $this->typeService->update($feedType, $request->validated());

        return redirect()
            ->route('feed.types.index')
            ->with('success', "Feed type \"{$feedType->name}\" updated.");
    }

    /**
     * Delete a feed type.
     *
     * A type referenced by any movement is refused with an explanation — never
     * deleted silently, never orphaning stock history.
     */
    public function destroy(Request $request, FeedType $feedType): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $feedType);

        $name = $feedType->name;

        try {
            $this->typeService->delete($feedType);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, "Feed type \"{$name}\" deleted.", 'feed.types.index');
    }
}
