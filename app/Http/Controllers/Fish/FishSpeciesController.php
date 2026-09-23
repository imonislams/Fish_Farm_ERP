<?php

namespace App\Http\Controllers\Fish;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fish\StoreFishSpeciesRequest;
use App\Http\Requests\Fish\UpdateFishSpeciesRequest;
use App\Models\FishSpecies;
use App\Services\Fish\FishSpeciesService;
use App\Services\Fish\FishStockService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Fish species management (master data).
 *
 * Thin controller: authorize, delegate to FishSpeciesService, return a view or a
 * redirect with a flash message. The "species in use cannot be deleted" business
 * rule lives in the service — this controller only turns the resulting
 * DomainException into a clear flash error rather than a stack trace.
 *
 * AUTHORIZATION: `permission:fish.species.manage` middleware on every route plus
 * the policy check inside each action.
 */
class FishSpeciesController extends Controller
{
    public function __construct(
        private readonly FishSpeciesService $speciesService,
        private readonly FishStockService $stockService,
    ) {}

    /** Paginated, searchable species list with live-stock counts (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));

        $species = FishSpecies::query()
            ->withCount(['stockings', 'harvests'])          // avoids a query per row
            ->search($search)
            ->orderBy('name')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        // Live stock per species, computed once for the whole page.
        $stockBySpecies = $this->stockService->stockBySpecies();

        return \Inertia\Inertia::render('Fish/Species/Index', [
            'title' => 'Fish Species',
            'currency' => \App\Support\Currency::symbol(),
            'species' => [
                'data' => collect($species->items())->map(fn (FishSpecies $s): array => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'local_name' => $s->local_name,
                    'scientific_name' => $s->scientific_name,
                    'default_price_per_kg' => $s->default_price_per_kg,
                    'is_active' => (bool) $s->is_active,
                    'stock' => $stockBySpecies[$s->id] ?? 0,
                    'records' => $s->stockings_count + $s->harvests_count,
                    'urls' => [
                        'edit' => route('fish.species.edit', $s, absolute: false),
                        'destroy' => route('fish.species.destroy', $s, absolute: false),
                    ],
                ])->all(),
                'current_page' => $species->currentPage(),
                'last_page' => $species->lastPage(),
                'total' => $species->total(),
                'from' => $species->firstItem(),
                'to' => $species->lastItem(),
                'links' => $species->linkCollection()->toArray(),
            ],
            'filters' => ['search' => $search],
        ]);
    }

    /** Show the create form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', FishSpecies::class);

        return \Inertia\Inertia::render('Fish/Species/Create', [
            'title' => 'New Fish Species',
        ]);
    }

    /** Persist a new species. */
    public function store(StoreFishSpeciesRequest $request): RedirectResponse
    {
        $species = $this->speciesService->create($request->validated());

        return redirect()
            ->route('fish.species.index')
            ->with('success', "Species \"{$species->name}\" created.");
    }

    /** Show the edit form (Inertia/React). */
    public function edit(FishSpecies $species): \Inertia\Response
    {
        Gate::authorize('update', $species);

        $species->loadCount(['stockings', 'harvests']);

        return \Inertia\Inertia::render('Fish/Species/Edit', [
            'title' => 'Edit — ' . $species->name,
            'species' => [
                'id' => $species->id,
                'name' => $species->name,
                'local_name' => $species->local_name,
                'scientific_name' => $species->scientific_name,
                'default_price_per_kg' => $species->default_price_per_kg,
                'description' => $species->description,
                'is_active' => (bool) $species->is_active,
                'stockings_count' => $species->stockings_count,
                'harvests_count' => $species->harvests_count,
                'created_at' => $species->created_at?->format('d M Y'),
            ],
        ]);
    }

    /** Persist changes. */
    public function update(UpdateFishSpeciesRequest $request, FishSpecies $species): RedirectResponse
    {
        Gate::authorize('update', $species);

        $this->speciesService->update($species, $request->validated());

        return redirect()
            ->route('fish.species.index')
            ->with('success', "Species \"{$species->name}\" updated.");
    }

    /**
     * Delete a species.
     *
     * A species referenced by any stocking or harvest is refused with an
     * explanation — never deleted silently, never orphaning stock history.
     */
    public function destroy(Request $request, FishSpecies $species): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $species);

        $name = $species->name;

        try {
            $this->speciesService->delete($species);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, "Species \"{$name}\" deleted.", 'fish.species.index');
    }
}
