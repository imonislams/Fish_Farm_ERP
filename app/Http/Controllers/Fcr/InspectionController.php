<?php

namespace App\Http\Controllers\Fcr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fcr\StoreInspectionRequest;
use App\Http\Requests\Fcr\UpdateInspectionRequest;
use App\Models\Inspection;
use App\Models\Pond;
use App\Services\Fcr\InspectionService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Pond inspections — the record of what was measured and concluded.
 *
 * Thin controller: authorize, validate via FormRequest, delegate to
 * InspectionService (which also advances the pond's schedule in the same
 * transaction), redirect.
 *
 * AUTHORIZATION: `permission:fcr.view` (read), `fcr.inspection.create` /
 * `fcr.inspection.update` (write) plus the policy check inside each action.
 */
class InspectionController extends Controller
{
    public function __construct(
        private readonly InspectionService $inspectionService,
    ) {}

    /** Paginated, filterable inspection list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');
        $status = (string) $request->query('status', '');
        $from = $request->query('from');
        $to = $request->query('to');

        // Only accept a status that is in the catalogue.
        $status = array_key_exists($status, config('fcr.health_statuses', [])) ? $status : '';

        $inspections = Inspection::query()
            ->with('pond:id,name,pond_number')                 // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($status !== '', fn($q) => $q->where('health_status', $status))
            ->when($from, fn($q) => $q->whereDate('inspected_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('inspected_on', '<=', $to))
            ->orderByDesc('inspected_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Fcr/Inspections/Index', [
            'title' => 'Pond Inspection',
            'inspections' => [
                'data' => collect($inspections->items())->map(fn (Inspection $i): array => [
                    'id' => $i->id,
                    'date' => $i->inspected_on?->toDateString(),
                    'pond' => $i->pond?->name,
                    'pond_number' => $i->pond?->pond_number,
                    'inspected_by' => $i->inspected_by,
                    'health' => $i->healthLabel(),
                    'health_tone' => $i->healthTone(),
                    'action_taken' => \Illuminate\Support\Str::limit($i->action_taken, 60),
                    'urls' => [
                        'show' => route('fcr.inspections.show', $i, absolute: false),
                        'edit' => route('fcr.inspections.edit', $i, absolute: false),
                        'destroy' => route('fcr.inspections.destroy', $i, absolute: false),
                    ],
                ])->all(),
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
                'total' => $inspections->total(),
                'from' => $inspections->firstItem(),
                'to' => $inspections->lastItem(),
                'links' => $inspections->linkCollection()->toArray(),
            ],
            'filters' => ['pond' => $pondId, 'status' => $status, 'from' => $from, 'to' => $to],
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'statusOptions' => collect(config('fcr.health_statuses', []))
                    ->map(fn(array $meta): string => $meta['label'])
                    ->all(),
            ],
            'statusCounts' => $this->inspectionService->statusCounts(),
            'statuses' => config('fcr.health_statuses', []),
        ]);
    }

    /** Show the create form (Inertia/React). */
    public function create(Request $request): \Inertia\Response
    {
        Gate::authorize('create', Inspection::class);

        return \Inertia\Inertia::render('Fcr/Inspections/Create', [
            'title' => 'New Inspection',
            'options' => $this->filterOptions(),
            'parameters' => config('fcr.parameters', []),
            'selectedPondId' => $request->query('pond'),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist an inspection. */
    public function store(StoreInspectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->inspectionService->actorId();

        $inspection = $this->inspectionService->record($data);

        return redirect()
            ->route('fcr.inspections.index', ['pond' => $inspection->pond_id])
            ->with('success', "Inspection recorded for \"{$inspection->pond->name}\" — {$inspection->healthLabel()}.");
    }

    /** Inspection details (Inertia/React). */
    public function show(Inspection $inspection): \Inertia\Response
    {
        Gate::authorize('view', $inspection);

        $inspection->load(['pond:id,name,pond_number', 'creator:id,name']);

        $schedule = $inspection->pond?->inspectionSchedule;

        return \Inertia\Inertia::render('Fcr/Inspections/Show', [
            'title' => 'Inspection — ' . ($inspection->pond?->name ?? ''),
            'inspection' => [
                'id' => $inspection->id,
                'pond' => $inspection->pond?->name,
                'pond_number' => $inspection->pond?->pond_number,
                'inspected_on' => $inspection->inspected_on?->toDateString(),
                'inspected_by' => $inspection->inspected_by,
                'health' => $inspection->healthLabel(),
                'health_tone' => $inspection->healthTone(),
                'action_taken' => $inspection->action_taken,
                'note' => $inspection->note,
                'recorded_by' => $inspection->creator?->name,
                'created_at' => $inspection->created_at?->format('d M Y'),
                'readings' => collect($inspection->parameterReadings())
                    ->map(fn ($value, $label): array => ['label' => $label, 'value' => $value])
                    ->values()
                    ->all(),
            ],
            'schedule' => $schedule ? [
                'frequency' => $schedule->frequencyLabel(),
                'last_completed_on' => $schedule->last_completed_on?->toDateString(),
                'next_due_on' => $schedule->next_due_on?->toDateString(),
                'status' => $schedule->statusLabel(),
                'status_tone' => $schedule->statusTone(),
            ] : null,
        ]);
    }

    /** Show the edit form (Inertia/React). */
    public function edit(Inspection $inspection): \Inertia\Response
    {
        Gate::authorize('update', $inspection);

        // Only the readings actually measured come back to the form.
        $readings = collect($inspection->parameterReadings())
            ->mapWithKeys(fn ($value, $label) => [strtolower(str_replace(' ', '_', $label)) => $value])
            ->all();

        return \Inertia\Inertia::render('Fcr/Inspections/Edit', [
            'title' => 'Edit Inspection',
            'inspection' => [
                'id' => $inspection->id,
                'pond_id' => $inspection->pond_id,
                'inspected_on' => $inspection->inspected_on?->toDateString(),
                'health_status' => $inspection->health_status,
                'inspected_by' => $inspection->inspected_by,
                'action_taken' => $inspection->action_taken,
                'note' => $inspection->note,
                'readings' => $this->rawReadings($inspection),
            ],
            'options' => $this->filterOptions(),
            'parameters' => config('fcr.parameters', []),
            'defaultDate' => $inspection->inspected_on?->toDateString() ?? now()->toDateString(),
        ]);
    }

    /** The raw numeric readings keyed by column name, for repopulating the form. */
    private function rawReadings(Inspection $inspection): array
    {
        $out = [];
        foreach (array_keys(config('fcr.parameters', [])) as $key) {
            $out[$key] = $inspection->{$key};
        }

        return $out;
    }

    /**
     * The pond + status option lists shared by the create/edit/index pages.
     *
     * @return array<string, array<string, string>>
     */
    private function filterOptions(): array
    {
        return [
            'pondOptions' => $this->pondOptions()->all(),
            'statusOptions' => collect(config('fcr.health_statuses', []))
                ->map(fn(array $meta): string => $meta['label'])
                ->all(),
        ];
    }

    /** Persist changes. */
    public function update(UpdateInspectionRequest $request, Inspection $inspection): RedirectResponse
    {
        Gate::authorize('update', $inspection);

        $this->inspectionService->update($inspection, $request->validated());

        return redirect()
            ->route('fcr.inspections.show', $inspection)
            ->with('success', 'Inspection updated.');
    }

    /** Delete an inspection. */
    public function destroy(Request $request, Inspection $inspection): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $inspection);

        $this->inspectionService->delete($inspection);

        return AsyncResponse::ok($request, 'Inspection deleted.', 'fcr.inspections.index');
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
}
