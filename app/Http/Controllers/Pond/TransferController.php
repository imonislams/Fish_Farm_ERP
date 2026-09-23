<?php

namespace App\Http\Controllers\Pond;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pond\StorePondTransferRequest;
use App\Models\FishSpecies;
use App\Models\Pond;
use App\Models\PondTransfer;
use App\Services\Fish\FishStockService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Pond Ledger — Transfers section ("Transferring fish into ponds").
 *
 * The New Transfers form and the Transfer History list. A transfer moves fish
 * from a source pond to a destination pond ATOMICALLY (both sides, or neither)
 * through FishStockService — the single write path for pond stock movement.
 *
 * AUTHORIZATION: `permission:pond_ledger.transfer.*` middleware plus the policy.
 */
class TransferController extends Controller
{
    public function __construct(
        private readonly FishStockService $stockService,
    ) {}

    /** The Transfers page: New Transfers form + Transfer History (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');

        $transfers = PondTransfer::query()
            ->with([
                'fromPond:id,name,pond_number',
                'toPond:id,name,pond_number',
                'species:id,name',
                'creator:id,name',
            ])                                                            // avoids N+1
            ->when($pondId, fn($q) => $q->involvingPond($pondId))
            ->orderByDesc('transferred_on')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Ledger/Transfers', [
            'title' => 'Transfers',
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'speciesOptions' => $this->speciesOptions()->all(),
            ],
            'transfers' => [
                'data' => collect($transfers->items())->map(fn (PondTransfer $t): array => [
                    'id' => $t->id,
                    'date' => $t->transferred_on?->toDateString(),
                    'from' => $t->fromPond?->name,
                    'from_number' => $t->fromPond?->pond_number,
                    'to' => $t->toPond?->name,
                    'to_number' => $t->toPond?->pond_number,
                    'species' => $t->species?->name,
                    'quantity' => number_format((int) $t->quantity),
                    'reference' => $t->reference,
                    'user' => $t->creator?->name ?? 'System',
                    'urls' => [
                        'destroy' => route('ledger.transfers.destroy', $t, absolute: false),
                    ],
                ])->all(),
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'total' => $transfers->total(),
                'from' => $transfers->firstItem(),
                'to' => $transfers->lastItem(),
                'links' => $transfers->linkCollection()->toArray(),
            ],
            'filters' => ['pond' => $pondId],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    /** Persist a transfer (atomic: source OUT + destination IN). */
    public function store(StorePondTransferRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->stockService->actorId();

        try {
            $transfer = $this->stockService->recordTransfer($data);
        } catch (\DomainException $e) {
            // The service guard is authoritative even if the form was bypassed.
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('ledger.transfers')
            ->with('success', "Transferred {$transfer->quantity} fish from \"{$transfer->fromPond->name}\" "
                . "to \"{$transfer->toPond->name}\".");
    }

    /** Delete a transfer (restores both sides) — refused if it would overdraw the destination. */
    public function destroy(Request $request, PondTransfer $transfer): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $transfer);

        try {
            $this->stockService->deleteTransfer($transfer);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, 'Transfer record deleted.', 'ledger.transfers');
    }

    /**
     * Ponds selectable in the form: id => "P-01 — Pond name".
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
     * Active species selectable in the form: id => name.
     *
     * @return Collection<int, string>
     */
    private function speciesOptions(): Collection
    {
        return FishSpecies::query()
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
