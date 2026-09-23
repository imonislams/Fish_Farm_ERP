<?php

namespace App\Http\Controllers\Pond;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pond\StorePondLedgerEntryRequest;
use App\Models\Pond;
use App\Models\PondLedgerEntry;
use App\Services\Pond\PondLedgerService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Pond ledger entries (transactions).
 *
 * Thin controller: authorize, validate via FormRequest, delegate the write to
 * PondLedgerService, redirect. The service is the only place a ledger row is
 * created or reversed (docs/BUSINESS_LOGIC.md §4 rule 1).
 *
 * AUTHORIZATION: `permission:ledger.*` middleware plus the policy check.
 */
class PondLedgerEntryController extends Controller
{
    public function __construct(
        private readonly PondLedgerService $ledgerService,
    ) {}

    /** Paginated, filterable entry list — "Pond Transactions" (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $pondId = $request->query('pond');
        $type = (string) $request->query('entry_type', '');
        $category = (string) $request->query('category', '');
        $search = trim((string) $request->query('search', ''));
        $from = $request->query('from');
        $to = $request->query('to');

        // Only accept a type that is in the catalogue.
        $type = array_key_exists($type, config('ledger.entry_types', [])) ? $type : '';

        $entries = PondLedgerEntry::query()
            ->with('pond:id,name,pond_number')                 // avoids N+1
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($type !== '', fn($q) => $q->where('entry_type', $type))
            ->when($category !== '', fn($q) => $q->where('category', $category))
            ->search($search)
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        // Totals for the CURRENT filter set — computed from the same conditions,
        // so the summary always matches the rows shown.
        $filtered = PondLedgerEntry::query()
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($type !== '', fn($q) => $q->where('entry_type', $type))
            ->when($category !== '', fn($q) => $q->where('category', $category))
            ->search($search)
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to));

        $income = (float) (clone $filtered)->where('entry_type', PondLedgerEntry::TYPE_CREDIT)->sum('amount');
        $expense = (float) (clone $filtered)->where('entry_type', PondLedgerEntry::TYPE_DEBIT)->sum('amount');

        return \Inertia\Inertia::render('Ledger/Transactions', [
            'title' => 'Pond Transactions',
            'currency' => \App\Support\Currency::symbol(),
            'entries' => [
                'data' => collect($entries->items())->map(fn (PondLedgerEntry $e): array => [
                    'id' => $e->id,
                    'date' => $e->entry_date?->toDateString(),
                    'pond' => $e->pond?->name,
                    'pond_number' => $e->pond?->pond_number,
                    'type_short' => $e->typeShortLabel(),
                    'type_tone' => $e->typeTone(),
                    'category' => $e->categoryLabel(),
                    'reference' => $e->reference,
                    'source_type' => $e->source_type,
                    'source_label' => $e->sourceTypeLabel(),
                    'amount' => (float) $e->amount,
                    'is_credit' => $e->isCredit(),
                    'urls' => [
                        'pond' => $e->pond ? route('ponds.show', $e->pond, absolute: false) : null,
                        'destroy' => route('ledger.transactions.destroy', $e, absolute: false),
                    ],
                ])->all(),
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total' => $entries->total(),
                'from' => $entries->firstItem(),
                'to' => $entries->lastItem(),
                'links' => $entries->linkCollection()->toArray(),
            ],
            'filters' => [
                'pond' => $pondId, 'entry_type' => $type, 'category' => $category,
                'search' => $search, 'from' => $from, 'to' => $to,
            ],
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'typeOptions' => collect(config('ledger.entry_types', []))
                    ->map(fn(array $meta): string => $meta['label'])
                    ->all(),
                'categoryOptions' => $this->categoryOptions(),
            ],
            'summary' => [
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'profit' => round($income - $expense, 2),
            ],
        ]);
    }

    /** Show the manual entry form (Inertia/React). */
    public function create(Request $request): \Inertia\Response
    {
        Gate::authorize('create', PondLedgerEntry::class);

        return \Inertia\Inertia::render('Ledger/Create', [
            'title' => 'New Ledger Entry',
            'options' => [
                'pondOptions' => $this->pondOptions()->all(),
                'typeOptions' => collect(config('ledger.entry_types', []))
                    ->map(fn(array $meta): string => $meta['label'])
                    ->all(),
            ],
            // Categories grouped by type so the form can show the right list.
            'categoriesByType' => config('ledger.categories', []),
            'defaultDate' => now()->toDateString(),
            // Pre-select a pond when arriving from a pond page.
            'selectedPondId' => $request->query('pond') ? (int) $request->query('pond') : null,
        ]);
    }

    /** Persist a manual entry. */
    public function store(StorePondLedgerEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        // A manual entry is always source_type 'manual'; generated entries come
        // only from services that own a source transaction.
        $data['source_type'] = PondLedgerEntry::SOURCE_MANUAL;
        $data['created_by'] = $this->ledgerService->actorId();

        $entry = $this->ledgerService->record($data);

        return redirect()
            ->route('ledger.transactions')
            ->with('success', "Recorded {$entry->typeShortLabel()} of " . number_format((float) $entry->amount, 2)
                . " for \"{$entry->pond->name}\".");
    }

    /**
     * Delete a hand-recorded entry.
     *
     * An entry generated by another module cannot be deleted here — the service
     * refuses it and explains, because its source must be removed instead.
     */
    public function destroy(Request $request, PondLedgerEntry $entry): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $entry);

        try {
            $this->ledgerService->delete($entry);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, 'Ledger entry deleted.', 'ledger.transactions');
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
     * All categories as a flat key => label map, for the filter dropdown.
     *
     * @return array<string, string>
     */
    private function categoryOptions(): array
    {
        $options = [];

        foreach (config('ledger.categories', []) as $categories) {
            foreach ($categories as $key => $label) {
                $options[$key] = $label;
            }
        }

        return $options;
    }
}
