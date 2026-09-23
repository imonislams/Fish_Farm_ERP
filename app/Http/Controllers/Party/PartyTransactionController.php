<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Http\Requests\Party\StorePartyTransactionRequest;
use App\Models\Party;
use App\Models\PartyTransaction;
use App\Services\Finance\PartyLedgerService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Party transactions (debit/credit entries).
 *
 * The balance is always derived — recording a transaction never stores a running
 * total (docs/BUSINESS_LOGIC.md §3).
 */
class PartyTransactionController extends Controller
{
    public function __construct(
        private readonly PartyLedgerService $ledger,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $partyId = $request->query('party');
        $type = (string) $request->query('entry_type', '');
        $from = $request->query('from');
        $to = $request->query('to');

        $transactions = PartyTransaction::query()
            ->with(['party:id,name', 'creator:id,name'])
            ->when($partyId, fn($q) => $q->where('party_id', $partyId))
            ->when($type !== '', fn($q) => $q->where('entry_type', $type))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Parties/Transactions/Index', [
            'title' => 'Party Transactions',
            'transactions' => [
                'data' => collect($transactions->items())->map(fn (PartyTransaction $t): array => [
                    'id' => $t->id,
                    'date' => $t->entry_date?->toDateString(),
                    'party' => $t->party?->name,
                    'type' => $t->typeLabel(),
                    'type_tone' => $t->typeTone(),
                    'is_debit' => $t->isDebit(),
                    'amount' => (float) $t->amount,
                    'reference' => $t->reference,
                    'description' => $t->description,
                    'urls' => [
                        'destroy' => route('parties.transactions.destroy', $t, absolute: false),
                    ],
                ])->all(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
                'links' => $transactions->linkCollection()->toArray(),
            ],
            'filters' => [
                'party' => $partyId, 'entry_type' => $type,
                'from' => $from, 'to' => $to,
            ],
            'options' => ['partyOptions' => $this->partyOptions()->all()],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $partyId = $request->query('party');
        $type = (string) $request->query('entry_type', '');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = PartyTransaction::query()
            ->with('party:id,name')
            ->when($partyId, fn($q) => $q->where('party_id', $partyId))
            ->when($type !== '', fn($q) => $q->where('entry_type', $type))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->get()
            ->map(fn(PartyTransaction $t): array => [
                $t->entry_date?->format('Y-m-d') ?? '',
                $t->party?->name ?? '',
                $t->typeLabel(),
                number_format((float) $t->amount, 2, '.', ''),
                $t->reference ?? '',
                $t->description ?? '',
            ]);

        return CsvExporter::download('party-transactions', [
            'Date',
            'Party',
            'Type',
            'Amount',
            'Reference',
            'Description',
        ], $rows);
    }

    public function create(Request $request): \Inertia\Response
    {
        Gate::authorize('create', PartyTransaction::class);

        return \Inertia\Inertia::render('Parties/Transactions/Create', [
            'title' => 'Record Party Transaction',
            'options' => ['partyOptions' => $this->partyOptions()->all()],
            'selectedPartyId' => $request->query('party') ? (int) $request->query('party') : null,
            'balanceByParty' => $this->ledger->balancesForParties(
                Party::query()->pluck('id')->all()
            ),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(StorePartyTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $transaction = $this->ledger->record($data);

        return AsyncResponse::ok(
            $request,
            'Party transaction recorded.',
            'parties.transactions',
        );
    }

    public function destroy(Request $request, PartyTransaction $transaction): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $transaction);

        $this->ledger->delete($transaction);

        return AsyncResponse::ok($request, 'Transaction deleted.', 'parties.transactions');
    }

    /** @return Collection<int, string> */
    private function partyOptions(): Collection
    {
        return Party::query()->orderBy('name')->pluck('name', 'id');
    }
}
