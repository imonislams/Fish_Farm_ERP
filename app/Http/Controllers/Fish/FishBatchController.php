<?php

namespace App\Http\Controllers\Fish;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fish\StoreFishBatchRequest;
use App\Http\Requests\Fish\UpdateFishBatchRequest;
use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\Pond;
use App\Services\Fish\FishBatchService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Fish batches (stocking cycles).
 *
 * A batch is a LABEL over movement records — its quantity is always derived by
 * FishBatchService from the stockings/mortalities/harvests tagged to it. The
 * controller never stores a quantity (single source of truth).
 *
 * AUTHORIZATION: `permission:fish.batch.*` middleware plus the policy per record.
 */
class FishBatchController extends Controller
{
    public function __construct(
        private readonly FishBatchService $batches,
    ) {}

    /** The batch list with real derived quantities and survival. */
    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $pondId = $request->query('pond');

        $batches = FishBatch::query()
            ->with(['pond:id,name,pond_number', 'species:id,name'])
            ->search($search)
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->orderByDesc('started_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        $quantities = $this->batches->currentQuantityForIds(
            collect($batches->items())->pluck('id')->all()
        );

        return \Inertia\Inertia::render('Fish/Batches/Index', [
            'title' => 'Fish Batches',
            'batches' => [
                'data' => collect($batches->items())->map(fn(FishBatch $b): array => [
                    'id' => $b->id,
                    'code' => $b->code,
                    'pond' => $b->pond?->name,
                    'pond_number' => $b->pond?->pond_number,
                    'species' => $b->species?->name,
                    'started_on' => $b->started_on?->format('Y-m-d'),
                    'initial_quantity' => (int) $b->initial_quantity,
                    'current_quantity' => $quantities[$b->id] ?? 0,
                    'survival' => $b->survivalRate(),
                    'status' => $b->statusLabel(),
                    'status_tone' => $b->statusTone(),
                    'urls' => [
                        'show' => route('fish.batches.show', $b, absolute: false),
                        'edit' => route('fish.batches.edit', $b, absolute: false),
                        'destroy' => route('fish.batches.destroy', $b, absolute: false),
                    ],
                ])->all(),
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'total' => $batches->total(),
                'from' => $batches->firstItem(),
                'to' => $batches->lastItem(),
                'links' => $batches->linkCollection()->toArray(),
            ],
            'summary' => [
                'activeCount' => FishBatch::query()->active()->count(),
                'activeFish' => $this->batches->activeTotalQuantity(),
                'total' => $batches->total(),
            ],
            'options' => ['pondOptions' => $this->pondOptions()],
            'statusOptions' => collect(config('finance.batch_statuses', []))
                ->map(fn(array $m): string => $m['label'])->all(),
            'filters' => ['search' => $search, 'status' => $status, 'pond' => $pondId],
        ]);
    }

    /** Batch detail: the movement counts behind its current quantity. */
    public function show(FishBatch $batch): \Inertia\Response
    {
        Gate::authorize('view', $batch);

        $batch->load(['pond:id,name,pond_number', 'species:id,name']);

        return \Inertia\Inertia::render('Fish/Batches/Show', [
            'title' => $batch->code,
            'batch' => [
                'id' => $batch->id,
                'code' => $batch->code,
                'pond' => $batch->pond?->name,
                'pond_id' => $batch->pond_id,
                'species' => $batch->species?->name,
                'started_on' => $batch->started_on?->format('Y-m-d'),
                'ended_on' => $batch->ended_on?->format('Y-m-d'),
                'initial_quantity' => (int) $batch->initial_quantity,
                'current_quantity' => $batch->currentQuantity(),
                'survival' => $batch->survivalRate(),
                'status' => $batch->statusLabel(),
                'status_tone' => $batch->statusTone(),
                'note' => $batch->note,
                'urls' => [
                    'pond' => $batch->pond ? route('ponds.show', $batch->pond, absolute: false) : null,
                    'edit' => route('fish.batches.edit', $batch, absolute: false),
                ],
            ],
            'inputs' => $this->batches->inputs($batch),
            'movements' => [
                'stockings' => $batch->stockings()->orderByDesc('stocked_on')->limit(10)->get()->map(fn($s) => [
                    'id' => $s->id,
                    'date' => $s->stocked_on?->format('Y-m-d'),
                    'quantity' => (int) $s->quantity,
                    'reference' => $s->reference,
                ])->all(),
                'mortalities' => $batch->mortalities()->orderByDesc('recorded_on')->limit(10)->get()->map(fn($m) => [
                    'id' => $m->id,
                    'date' => $m->recorded_on?->format('Y-m-d'),
                    'quantity' => (int) $m->quantity,
                    'reference' => $m->reference,
                ])->all(),
                'harvests' => $batch->harvests()->orderByDesc('harvested_on')->limit(10)->get()->map(fn($h) => [
                    'id' => $h->id,
                    'date' => $h->harvested_on?->format('Y-m-d'),
                    'quantity' => (int) $h->quantity,
                ])->all(),
            ],
        ]);
    }

    /** Show the create form. */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', FishBatch::class);

        return \Inertia\Inertia::render('Fish/Batches/Create', [
            'title' => 'New Fish Batch',
            'options' => [
                'pondOptions' => $this->pondOptions(),
                'speciesOptions' => $this->speciesOptions(),
                'statusOptions' => collect(config('finance.batch_statuses', []))
                    ->map(fn(array $m): string => $m['label'])->all(),
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a new batch (and its opening stocking, when given). */
    public function store(StoreFishBatchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $batch = $this->batches->create($data);

        return redirect()
            ->route('fish.batches.show', $batch)
            ->with('success', "Batch \"{$batch->code}\" created.");
    }

    /** Show the edit form. */
    public function edit(FishBatch $batch): \Inertia\Response
    {
        Gate::authorize('update', $batch);

        return \Inertia\Inertia::render('Fish/Batches/Edit', [
            'title' => 'Edit — ' . $batch->code,
            'batch' => [
                'id' => $batch->id,
                'code' => $batch->code,
                'pond' => $batch->pond?->name,
                'species' => $batch->species?->name,
                'started_on' => $batch->started_on?->format('Y-m-d'),
                'ended_on' => $batch->ended_on?->format('Y-m-d'),
                'initial_quantity' => (int) $batch->initial_quantity,
                'current_quantity' => $batch->currentQuantity(),
                'status' => $batch->status,
                'note' => $batch->note,
            ],
            'statusOptions' => collect(config('finance.batch_statuses', []))
                ->map(fn(array $m): string => $m['label'])->all(),
        ]);
    }

    /** Persist batch header changes (status / end date / note). */
    public function update(UpdateFishBatchRequest $request, FishBatch $batch): RedirectResponse
    {
        $this->batches->update($batch, $request->validated());

        return redirect()
            ->route('fish.batches.show', $batch)
            ->with('success', "Batch \"{$batch->code}\" updated.");
    }

    /** Delete a batch. Refused while it still has movement records. */
    public function destroy(Request $request, FishBatch $batch): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $batch);

        $code = $batch->code;

        try {
            $this->batches->delete($batch);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, "Batch \"{$code}\" deleted.", 'fish.batches.index');
    }

    /**
     * Ponds as id => "P-01 — Pond name".
     *
     * @return array<int, string>
     */
    private function pondOptions(): array
    {
        return Pond::query()
            ->orderBy('pond_number')
            ->get(['id', 'pond_number', 'name'])
            ->mapWithKeys(fn(Pond $p): array => [$p->id => "{$p->pond_number} — {$p->name}"])
            ->all();
    }

    /**
     * Species as id => name.
     *
     * @return array<int, string>
     */
    private function speciesOptions(): array
    {
        return FishSpecies::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
