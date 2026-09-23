<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Services\Finance\PartyLedgerService;
use App\Support\CsvExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Party ledger — every party's balance at a glance.
 *
 * Derived by PartyLedgerService: opening + debits − credits. Positive means the
 * party owes the farm.
 */
class PartyLedgerController extends Controller
{
    public function __construct(
        private readonly PartyLedgerService $ledger,
    ) {}

    public function __invoke(Request $request): \Inertia\Response
    {
        $only = (string) $request->query('only', '');

        $parties = Party::query()->orderBy('name')->get();
        $balances = $this->ledger->balancesForParties($parties->pluck('id')->all());

        $filtered = $parties
            ->filter(function (Party $p) use ($balances, $only): bool {
                $balance = $balances[$p->id] ?? 0.0;

                return match ($only) {
                    'due' => $balance > 0,
                    'credit' => $balance < 0,
                    'settled' => abs($balance) < 0.005,
                    default => true,
                };
            })
            ->values()
            ->map(fn (Party $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->typeLabel(),
                'phone' => $p->phone,
                'opening' => (float) $p->opening_balance,
                'balance' => $balances[$p->id] ?? 0.0,
            ])
            ->all();

        return \Inertia\Inertia::render('Parties/Ledger', [
            'title' => 'Party Ledger',
            'parties' => $filtered,
            'only' => $only,
            'summary' => [
                'totalReceivable' => array_sum(array_filter($balances, fn($b) => $b > 0)),
                'totalPayable' => abs(array_sum(array_filter($balances, fn($b) => $b < 0))),
            ],
        ]);
    }

    /** Export the party ledger as CSV. */
    public function export(): StreamedResponse
    {
        $parties = Party::query()->orderBy('name')->get();
        $balances = $this->ledger->balancesForParties($parties->pluck('id')->all());

        $rows = $parties->map(fn(Party $p): array => [
            $p->name,
            $p->typeLabel(),
            $p->phone ?? '',
            number_format((float) $p->opening_balance, 2, '.', ''),
            number_format($balances[$p->id] ?? 0, 2, '.', ''),
        ]);

        return CsvExporter::download('party-ledger', [
            'Party',
            'Type',
            'Phone',
            'Opening Balance',
            'Balance',
        ], $rows);
    }
}
