<?php

namespace App\Http\Controllers\Pond;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pond\StorePondRequest;
use App\Http\Requests\Pond\UpdatePondRequest;
use App\Models\FishStocking;
use App\Models\Pond;
use App\Services\Pond\PondService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Pond management.
 *
 * Responsibilities (docs/ARCHITECTURE.md §3): handle the request, authorize,
 * validate (via FormRequest), call the service, return a view or a redirect with
 * a flash message. No query building beyond what the service offers, no business
 * rules, no calculations.
 *
 * AUTHORIZATION: every route carries `permission:pond.*` middleware (see
 * routes/web.php) AND each action re-asserts the policy, so a user cannot reach a
 * pond by editing an id in the URL. Hiding a button in Blade is cosmetic only.
 */
class PondController extends Controller
{
    public function __construct(
        private readonly PondService $pondService,
    ) {}

    /**
     * Paginated, searchable, filterable pond list.
     *
     * MIGRATION NOTE: the response is now Inertia/React (`Inertia::render`).
     * The query, filters and pagination are UNCHANGED — only the transport is.
     * Each row carries its own show/edit/destroy URLs (built server-side with
     * `route()`), so React never hard-codes a URL.
     */
    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $type = $request->query('type');

        $ponds = $this->pondService->paginate([
            'search' => $search,
            'status' => $status,
            'type' => $type,
        ], (int) config('fishfarm.pagination.default', 15));

        $pondIds = collect($ponds->items())->pluck('id')->all();
        $stockService = app(\App\Services\Fish\FishStockService::class);
        $fcrService = app(\App\Services\Fcr\FcrService::class);

        // Batch computations (a fixed number of queries — no N+1 on the list):
        // live stock, total stocked, survival, biomass, species and FCR per pond.
        $liveByPond = $stockService->stockForPonds($pondIds);
        $stockedByPond = $stockService->stockedForPonds($pondIds);
        $survivalByPond = $stockService->survivalForPonds($pondIds);
        $biomassByPond = $stockService->biomassForPonds($pondIds);
        $fcrByPond = $fcrService->forPondsBatch($pondIds);
        $speciesByPond = FishStocking::query()
            ->whereIn('pond_id', $pondIds)
            ->with('species:id,name')
            ->get()
            ->groupBy('pond_id')
            ->map(fn ($rows) => $rows->pluck('species.name')->filter()->unique()->values()->all());

        return \Inertia\Inertia::render('Ponds/Index', [
            'title' => 'All Ponds',
            'ponds' => [
                'data' => collect($ponds->items())->map(fn (Pond $pond): array => [
                    'id' => $pond->id,
                    'pond_number' => $pond->pond_number,
                    'name' => $pond->name,
                    'type' => $pond->type?->name,
                    'size' => $pond->sizeDisplay(),
                    'depth' => $pond->depthDisplay(),
                    'location' => $pond->location,
                    'water_source' => $pond->water_source,
                    'status' => $pond->statusLabel(),
                    'status_tone' => $pond->statusTone(),
                    'stocked' => $stockedByPond[$pond->id] ?? 0,
                    'live' => $liveByPond[$pond->id] ?? 0,
                    'biomass_kg' => $biomassByPond[$pond->id] ?? null,
                    'fcr' => ($fcr = $fcrByPond[$pond->id] ?? null)?->display(),
                    'fcr_available' => $fcr?->isAvailable() ?? false,
                    'survival' => $survivalByPond[$pond->id] ?? null,
                    'species' => $speciesByPond[$pond->id] ?? [],
                    'urls' => [
                        'show' => route('ponds.show', $pond, absolute: false),
                        'edit' => route('ponds.edit', $pond, absolute: false),
                        'destroy' => route('ponds.destroy', $pond, absolute: false),
                    ],
                ])->all(),
                'current_page' => $ponds->currentPage(),
                'last_page' => $ponds->lastPage(),
                'total' => $ponds->total(),
                'from' => $ponds->firstItem(),
                'to' => $ponds->lastItem(),
                'links' => $ponds->linkCollection()->toArray(),
            ],
            'typeOptions' => $this->pondService->typeOptions()->all(),
            'statusOptions' => collect(config('ponds.statuses', []))
                ->map(fn(array $meta): string => $meta['label'])
                ->all(),
            'filters' => ['search' => $search, 'status' => $status, 'type' => $type],
        ]);
    }

    /** Show the create form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', Pond::class);

        // Only active types may be assigned to a new pond.
        return \Inertia\Inertia::render('Ponds/Create', [
            'title' => 'New Pond',
            'options' => $this->formOptions(activeOnly: true),
        ]);
    }

    /** Persist a new pond. */
    public function store(StorePondRequest $request): RedirectResponse
    {
        $pond = $this->pondService->create($request->validated());

        return redirect()
            ->route('ponds.show', $pond)
            ->with('success', "Pond \"{$pond->name}\" created.");
    }

    /**
     * Pond details.
     *
     * Shows the pond's REAL fish-stock figures from the Fish Stock module
     * (Phase 3) and honest empty states for the modules that do not exist yet.
     */
    public function show(Pond $pond): \Inertia\Response
    {
        Gate::authorize('view', $pond);

        $pond->load('type:id,name,is_active');

        $stockService = app(\App\Services\Fish\FishStockService::class);
        $ledgerService = app(\App\Services\Pond\PondLedgerService::class);
        $fcrService = app(\App\Services\Fcr\FcrService::class);
        $growthService = app(\App\Services\Fcr\GrowthService::class);

        $fcr = $fcrService->forPond($pond);
        $inputs = $fcrService->inputs($pond);
        $growth = $growthService->summaryForPond($pond);
        $band = $fcr->band();

        return \Inertia\Inertia::render('Ponds/Show', [
            'title' => $pond->name,
            'pond' => [
                'id' => $pond->id,
                'pond_number' => $pond->pond_number,
                'name' => $pond->name,
                'type' => $pond->type?->name,
                'type_inactive' => $pond->type ? ! $pond->type->is_active : false,
                'size' => $pond->sizeDisplay(),
                'depth' => $pond->depthDisplay(),
                'location' => $pond->location,
                'water_source' => $pond->water_source,
                'status' => $pond->statusLabel(),
                'status_tone' => $pond->statusTone(),
                'is_active' => (bool) $pond->is_active,
                'description' => $pond->description,
                'created_at' => $pond->created_at?->format('d M Y'),
                'updated_at' => $pond->updated_at?->format('d M Y'),
            ],
            'stock' => [
                'live' => $stockService->currentStock($pond),
                'stocked' => (int) $pond->stockings()->sum('quantity'),
                'mortality' => (int) $pond->mortalities()->sum('quantity'),
                'harvested' => (int) $pond->harvests()->sum('quantity'),
                'inspection_count' => $pond->inspections()->count(),
                // Derived figures — null means "not enough data", never a fake 0.
                'biomass_kg' => $stockService->biomassKg($pond),
                'survival' => $stockService->survivalRate($pond),
                // Species currently stocked in this pond (real records).
                'species' => $pond->stockings()
                    ->with('species:id,name')
                    ->get()
                    ->pluck('species.name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
            ],
            'ledger' => $ledgerService->pondSummary($pond),
            'fcr' => [
                'available' => $fcr->isAvailable(),
                'display' => $fcr->display(),
                'reason' => $fcr->reason(),
                'gain_kg' => $fcr->isAvailable() ? $fcr->weightGainKg : 0,
                'feed_consumed_kg' => $inputs['feed_consumed_kg'],
                'band_tone' => config("fcr.fcr_bands.{$band}.tone", 'default'),
            ],
            'growth' => [
                'samples' => $growth['samples'],
                'latest_g' => $growth['latest_g'],
            ],
        ]);
    }

    /** Show the edit form (Inertia/React). */
    public function edit(Pond $pond): \Inertia\Response
    {
        Gate::authorize('update', $pond);

        return \Inertia\Inertia::render('Ponds/Edit', [
            'title' => 'Edit — ' . $pond->name,
            'pond' => $this->pondPayload($pond),
            // Null when deletable; a reason string when it cannot be deleted.
            'deleteBlockReason' => $this->pondService->deletionBlockReason($pond),
            // Keep the pond's own type selectable even if it has since been
            // deactivated, otherwise saving the form would change it by accident.
            'options' => $this->formOptions(activeOnly: false),
        ]);
    }

    /** Persist changes. */
    public function update(UpdatePondRequest $request, Pond $pond): RedirectResponse
    {
        Gate::authorize('update', $pond);

        $this->pondService->update($pond, $request->validated());

        return redirect()
            ->route('ponds.show', $pond)
            ->with('success', "Pond \"{$pond->name}\" updated.");
    }

    /**
     * Export the (filtered) pond list as CSV.
     *
     * Real rows only — the same filters as the list, no sample data. Streamed and
     * UTF-8-with-BOM so Excel opens Bangla text and ৳ correctly (docs/ERP-UI-UX.md).
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $type = $request->query('type');

        $rows = Pond::query()
            ->with('type:id,name')
            ->search($search)
            ->when(! empty($type), fn ($q) => $q->ofType($type))
            ->when(! empty($status), fn ($q) => $q->status($status))
            ->orderBy('pond_number')
            ->get()
            ->map(fn (Pond $pond): array => [
                $pond->pond_number,
                $pond->name,
                $pond->type?->name ?? '',
                $pond->sizeDisplay(),
                $pond->depthDisplay(),
                $pond->location ?? '',
                $pond->water_source ?? '',
                $pond->statusLabel(),
                $pond->created_at?->format('Y-m-d') ?? '',
            ]);

        return CsvExporter::download('ponds', [
            'Pond No', 'Name', 'Type', 'Size', 'Depth', 'Location', 'Water Source', 'Status', 'Created',
        ], $rows);
    }

    /**
     * Delete a pond.
     *
     * A pond still holding live fish (or with stock movement history) is refused
     * by PondService with a DomainException, surfaced here as a clear flash error
     * rather than a stack trace.
     */
    public function destroy(Request $request, Pond $pond): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $pond);

        $name = $pond->name;

        try {
            $this->pondService->delete($pond);
        } catch (\DomainException $e) {
            // Business-rule refusal: a flash error normally, a 422 + error toast
            // for the async layer (docs/ERP-UI-UX.md "Error states").
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, "Pond \"{$name}\" deleted.", 'ponds.index');
    }

    /**
     * Status options as key => label, from the central catalogue.
     *
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return collect(config('ponds.statuses', []))
            ->map(fn(array $meta): string => $meta['label'])
            ->all();
    }

    /**
     * All option lists the create/edit forms need, in one place.
     *
     * @return array<string, array<string, string>>
     */
    private function formOptions(bool $activeOnly): array
    {
        return [
            'types' => $this->pondService->typeOptions($activeOnly)->all(),
            'statuses' => $this->statusOptions(),
            'sizeUnits' => $this->unitOptions('size_units'),
            'depthUnits' => $this->unitOptions('depth_units'),
        ];
    }

    /**
     * A plain array for the React edit/summary views (no Eloquent object leaks
     * to the client, and formatting stays server-side).
     *
     * @return array<string, mixed>
     */
    private function pondPayload(Pond $pond): array
    {
        return [
            'id' => $pond->id,
            'pond_number' => $pond->pond_number,
            'name' => $pond->name,
            'pond_type_id' => $pond->pond_type_id,
            'type' => $pond->type?->name,
            'size' => $pond->size,
            'size_unit' => $pond->size_unit,
            'depth' => $pond->depth,
            'depth_unit' => $pond->depth_unit,
            'location' => $pond->location,
            'water_source' => $pond->water_source,
            'status' => $pond->status,
            'status_label' => $pond->statusLabel(),
            'status_tone' => $pond->statusTone(),
            'description' => $pond->description,
            'created_at' => $pond->created_at?->format('d M Y'),
        ];
    }

    /**
     * Unit options as key => label for a given catalogue section.
     *
     * @return array<string, string>
     */
    private function unitOptions(string $section): array
    {
        return collect(config("ponds.{$section}", []))
            ->map(fn(array $meta): string => $meta['label'])
            ->all();
    }
}
