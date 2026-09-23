<?php

namespace App\Http\Controllers\Fcr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fcr\StoreGrowthRecordRequest;
use App\Http\Requests\Fcr\UpdateGrowthRecordRequest;
use App\Models\GrowthRecord;
use App\Models\Pond;
use App\Services\Fcr\GrowthService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Growth monitoring — sampled average weights over time.
 *
 * Thin controller: authorize, validate via FormRequest, delegate to GrowthService,
 * redirect. All derived figures (trend, gain, daily rate) are computed in the
 * service so no view performs arithmetic.
 *
 * AUTHORIZATION: `permission:growth.view` (read) / `growth.create` (write) plus
 * the policy check inside each action.
 */
class GrowthController extends Controller
{
    public function __construct(
        private readonly GrowthService $growthService,
    ) {}

    /** Paginated, filterable growth sample list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');
        $from = $request->query('from');
        $to = $request->query('to');

        $records = GrowthRecord::query()
            ->with('pond:id,name,pond_number')                 // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($from, fn($q) => $q->whereDate('sampled_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sampled_on', '<=', $to))
            ->orderByDesc('sampled_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        $summaries = $pondId
            ? $this->growthService->summariesForPonds([$pondId])
            : $this->growthService->summariesForPonds(Pond::query()->pluck('id')->all());

        $pondOptions = $this->pondOptions()->all();

        return \Inertia\Inertia::render('Fcr/Growth/Index', [
            'title' => 'Growth Monitoring',
            'records' => [
                'data' => collect($records->items())->map(fn (GrowthRecord $r): array => [
                    'id' => $r->id,
                    'date' => $r->sampled_on?->toDateString(),
                    'pond' => $r->pond?->name,
                    'pond_number' => $r->pond?->pond_number,
                    'avg_weight' => $r->weightDisplay(),
                    'in_kg' => $r->weightKgDisplay(),
                    'sample_size' => $r->sample_size !== null ? number_format($r->sample_size) : '—',
                    'note' => $r->note,
                    'urls' => [
                        'edit' => route('fcr.growth.edit', $r, absolute: false),
                        'destroy' => route('fcr.growth.destroy', $r, absolute: false),
                    ],
                ])->all(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'links' => $records->linkCollection()->toArray(),
            ],
            // Only ponds that actually have samples, keyed label-side for display.
            'summaries' => collect($summaries)
                ->filter(fn ($s) => $s['samples'] > 0)
                ->map(fn ($s, $id): array => [
                    'pond' => $pondOptions[$id] ?? ('#'.$id),
                    'samples' => $s['samples'],
                    'first_g' => $s['first_g'],
                    'latest_g' => $s['latest_g'],
                    'gain_g' => $s['gain_g'],
                    'daily_gain_g' => $s['daily_gain_g'],
                ])
                ->values()
                ->all(),
            'filters' => ['pond' => $pondId, 'from' => $from, 'to' => $to],
            'options' => ['pondOptions' => $pondOptions],
        ]);
    }

    /** Show the sample form (Inertia/React). */
    public function create(Request $request): \Inertia\Response
    {
        Gate::authorize('create', GrowthRecord::class);

        return \Inertia\Inertia::render('Fcr/Growth/Create', [
            'title' => 'Record Growth Sample',
            'options' => ['pondOptions' => $this->pondOptions()->all()],
            'selectedPondId' => $request->query('pond'),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a sample. */
    public function store(StoreGrowthRecordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->growthService->actorId();

        $record = $this->growthService->record($data);

        return redirect()
            ->route('fcr.growth.index', ['pond' => $record->pond_id])
            ->with('success', "Growth sample of {$record->avg_weight_g} g recorded.");
    }

    /** Show the edit form (Inertia/React). */
    public function edit(GrowthRecord $growth): \Inertia\Response
    {
        Gate::authorize('update', $growth);

        return \Inertia\Inertia::render('Fcr/Growth/Edit', [
            'title' => 'Edit Growth Sample',
            'record' => [
                'id' => $growth->id,
                'pond_id' => $growth->pond_id,
                'sampled_on' => $growth->sampled_on?->toDateString(),
                'avg_weight_g' => $growth->avg_weight_g,
                'sample_size' => $growth->sample_size,
                'note' => $growth->note,
            ],
            'options' => ['pondOptions' => $this->pondOptions()->all()],
            'defaultDate' => $growth->sampled_on?->toDateString() ?? now()->toDateString(),
        ]);
    }

    /** Persist changes. */
    public function update(UpdateGrowthRecordRequest $request, GrowthRecord $growth): RedirectResponse
    {
        Gate::authorize('update', $growth);

        $this->growthService->update($growth, $request->validated());

        return redirect()
            ->route('fcr.growth.index', ['pond' => $growth->pond_id])
            ->with('success', 'Growth sample updated.');
    }

    /** Delete a sample. */
    public function destroy(Request $request, GrowthRecord $growth): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $growth);

        $pondId = $growth->pond_id;

        $this->growthService->delete($growth);

        return AsyncResponse::ok(
            $request,
            'Growth sample deleted.',
            'fcr.growth.index',
            ['pond' => $pondId],
        );
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
