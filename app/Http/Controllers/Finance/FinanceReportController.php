<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseEntry;
use App\Models\IncomeEntry;
use App\Models\Sale;
use App\Services\Finance\CustomerBalanceService;
use App\Services\Finance\LedgerRules;
use App\Services\Finance\SupplierBalanceService;
use App\Support\CsvExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Finance profit & loss — income minus expense for a period.
 *
 * A NEGATIVE result is a real loss and is displayed as one, never clamped to zero
 * (docs/BUSINESS_LOGIC.md §3). The arithmetic comes from LedgerRules.
 */
class FinanceReportController extends Controller
{
    public function __construct(
        private readonly LedgerRules $rules,
        private readonly CustomerBalanceService $customerBalances,
        private readonly SupplierBalanceService $supplierBalances,
    ) {}

    public function profitLoss(Request $request): \Inertia\Response
    {
        [$from, $to] = $this->range($request);

        return \Inertia\Inertia::render('Finance/ProfitLoss', [
            'title' => 'Profit & Loss',
            'filters' => ['from' => $from, 'to' => $to],
            'generatedAt' => now()->format('d M Y H:i'),
            'figures' => $this->figures($from, $to),
        ]);
    }

    /** CSV of the profit & loss summary. */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $f = $this->figures($from, $to);

        $rows = [
            ['Fish sales', number_format($f['sales'], 2, '.', '')],
            ['Other income', number_format($f['miscIncome'], 2, '.', '')],
            ['Total income', number_format($f['income'], 2, '.', '')],
            ['Total expense', number_format($f['expense'], 2, '.', '')],
            ['Net profit', number_format($f['profit'], 2, '.', '')],
            ['Total receivable', number_format($f['receivable'], 2, '.', '')],
            ['Total payable', number_format($f['payable'], 2, '.', '')],
        ];

        return CsvExporter::download("profit-loss-{$from}-to-{$to}", ['Line', 'Amount'], $rows);
    }

    /**
     * @return array<string, float>
     */
    private function figures(string $from, string $to): array
    {
        $sales = (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('total');
        $misc = (float) IncomeEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->sum('amount');
        $expense = (float) ExpenseEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->sum('amount');

        return [
            'sales' => round($sales, 2),
            'miscIncome' => round($misc, 2),
            'income' => round($sales + $misc, 2),
            'expense' => round($expense, 2),
            // A loss is returned as a negative figure, never clamped.
            'profit' => $this->rules->netProfit($sales + $misc, $expense),
            'receivable' => $this->customerBalances->totalReceivable(),
            'payable' => $this->supplierBalances->totalPayable(),
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function range(Request $request): array
    {
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
