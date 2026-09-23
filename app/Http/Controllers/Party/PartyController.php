<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Http\Requests\Party\SavePartyRequest;
use App\Models\Party;
use App\Services\Finance\PartyLedgerService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Party management (generic ledger counterparties).
 *
 * The party BALANCE is derived by PartyLedgerService — never stored
 * (docs/BUSINESS_LOGIC.md §3).
 */
class PartyController extends Controller
{
    public function __construct(
        private readonly PartyLedgerService $ledger,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');

        $parties = Party::query()
            ->withCount('transactions')   // feeds isDeletable() without a query per row
            ->search($search)
            ->when($type !== '', fn($q) => $q->ofType($type))
            ->orderBy('name')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        $balances = $this->ledger->balancesForParties($parties->pluck('id')->all());

        return \Inertia\Inertia::render('Parties/Index', [
            'title' => 'Parties',
            'parties' => [
                'data' => collect($parties->items())->map(fn (Party $p): array => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'type' => $p->typeLabel(),
                    'phone' => $p->phone,
                    'address' => $p->address,
                    'opening' => (float) $p->opening_balance,
                    'balance' => $balances[$p->id] ?? 0.0,
                    'is_active' => (bool) $p->is_active,
                    'deletable' => $p->isDeletable(),
                    'urls' => [
                        'edit' => route('parties.edit', $p, absolute: false),
                        'destroy' => route('parties.destroy', $p, absolute: false),
                    ],
                ])->all(),
                'current_page' => $parties->currentPage(),
                'last_page' => $parties->lastPage(),
                'total' => $parties->total(),
                'from' => $parties->firstItem(),
                'to' => $parties->lastItem(),
                'links' => $parties->linkCollection()->toArray(),
            ],
            'filters' => ['search' => $search, 'type' => $type],
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');

        $parties = Party::query()
            ->search($search)
            ->when($type !== '', fn($q) => $q->ofType($type))
            ->orderBy('name')
            ->get();

        $balances = $this->ledger->balancesForParties($parties->pluck('id')->all());

        $rows = $parties->map(fn(Party $p): array => [
            $p->name,
            $p->typeLabel(),
            $p->phone ?? '',
            number_format((float) $p->opening_balance, 2, '.', ''),
            number_format($balances[$p->id] ?? 0, 2, '.', ''),
            $p->is_active ? 'Active' : 'Inactive',
        ]);

        return CsvExporter::download('parties', [
            'Name',
            'Type',
            'Phone',
            'Opening Balance',
            'Balance',
            'Status',
        ], $rows);
    }

    public function create(): \Inertia\Response
    {
        Gate::authorize('create', Party::class);

        return \Inertia\Inertia::render('Parties/Create', [
            'title' => 'New Party',
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    public function store(SavePartyRequest $request): RedirectResponse
    {
        $party = Party::create($request->validated());

        return AsyncResponse::ok($request, "Party \"{$party->name}\" created.", 'parties.index');
    }

    public function edit(Party $party): \Inertia\Response
    {
        Gate::authorize('update', $party);

        return \Inertia\Inertia::render('Parties/Edit', [
            'title' => 'Edit — ' . $party->name,
            'party' => [
                'id' => $party->id,
                'name' => $party->name,
                'type' => $party->type,
                'phone' => $party->phone,
                'address' => $party->address,
                'opening_balance' => $party->opening_balance,
                'note' => $party->note,
                'is_active' => (bool) $party->is_active,
                'deletable' => $party->isDeletable(),
                'transactions_count' => $party->transactions()->count(),
            ],
            'typeOptions' => $this->typeOptions(),
            'balance' => $this->ledger->balance($party),
        ]);
    }

    public function update(SavePartyRequest $request, Party $party): RedirectResponse
    {
        Gate::authorize('update', $party);

        $party->update($request->validated());

        return AsyncResponse::ok($request, "Party \"{$party->name}\" updated.", 'parties.index');
    }

    public function destroy(Request $request, Party $party): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $party);

        $name = $party->name;

        try {
            $this->ledger->guardDeletable($party);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        $party->delete();

        return AsyncResponse::ok($request, "Party \"{$name}\" deleted.", 'parties.index');
    }

    /** @return array<string, string> */
    private function typeOptions(): array
    {
        return collect(config('finance.party_types', []))
            ->map(fn(array $meta): string => $meta['label'])
            ->all();
    }
}
