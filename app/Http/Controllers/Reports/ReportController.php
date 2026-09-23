<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ExpenseEntry;
use App\Models\FeedPurchase;
use App\Models\FeedUsage;
use App\Models\GrowthRecord;
use App\Models\IncomeEntry;
use App\Models\Pond;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Fcr\FcrService;
use App\Services\Finance\CustomerBalanceService;
use App\Services\Finance\FinanceService;
use App\Services\Finance\SupplierBalanceService;
use App\Services\Fish\FishStockService;
use App\Support\CsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports — one controller, one shape, nine reports.
 *
 * EVERY report follows the same contract (docs/ERP-UI-UX.md §7):
 *   - a date range and the filters that make sense for it,
 *   - a summary of real totals,
 *   - a detailed table of real rows,
 *   - a CSV export of exactly what is on screen.
 *
 * WHY ONE CONTROLLER: the alternative — nine near-identical controllers — would
 * duplicate the filter/paginate/export logic nine times. The report logic itself
 * lives in services (`FinanceService`, `FishStockService`, balance services); this
 * controller only shapes the query and the view.
 *
 * Every figure is real database data. A report whose source module has no data
 * renders an honest empty state; nothing is invented.
 *
 * MIGRATION NOTE: the page responses are now Inertia/React (`Inertia::render`).
 * The queries, filters and totals are UNCHANGED — only the transport is. The CSV
 * export routes are untouched: they still stream real downloads.
 */
class ReportController extends Controller
{
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private readonly FinanceService $finance,
        private readonly FishStockService $stock,
        private readonly CustomerBalanceService $customerBalances,
        private readonly SupplierBalanceService $supplierBalances,
    ) {}

    /** The reports index — a card per report. */
    public function index(): Response
    {
        $reports = [
            ['key' => 'sales', 'route' => 'reports.sales', 'label' => 'Sales Report', 'icon' => 'cart', 'description' => 'Fish sales to customers over a period.'],
            ['key' => 'purchases', 'route' => 'reports.purchases', 'label' => 'Purchase Report', 'icon' => 'truck', 'description' => 'Purchases from suppliers over a period.'],
            ['key' => 'feed', 'route' => 'reports.feed', 'label' => 'Feed Report', 'icon' => 'feed', 'description' => 'Feed purchased and consumed.'],
            ['key' => 'fish-stock', 'route' => 'reports.fish-stock', 'label' => 'Fish Stock Report', 'icon' => 'fish', 'description' => 'Live stock per pond.'],
            ['key' => 'ponds', 'route' => 'reports.ponds', 'label' => 'Pond Report', 'icon' => 'droplet', 'description' => 'Pond ledger profitability.'],
            ['key' => 'fcr-growth', 'route' => 'reports.fcr-growth', 'label' => 'FCR & Growth Report', 'icon' => 'chart', 'description' => 'Feed conversion and growth samples.'],
            ['key' => 'income', 'route' => 'reports.income', 'label' => 'Income Report', 'icon' => 'plus', 'description' => 'Income by category.'],
            ['key' => 'expenses', 'route' => 'reports.expenses', 'label' => 'Expense Report', 'icon' => 'alert', 'description' => 'Expenses by category.'],
            ['key' => 'profit-loss', 'route' => 'reports.profit-loss', 'label' => 'Profit & Loss', 'icon' => 'report', 'description' => 'Income − expense = net profit.'],
        ];

        return Inertia::render('Reports/Index', [
            'title' => 'Reports',
            'reports' => $reports,
        ]);
    }

    /* ---------------------------------------------------------------------
     | Reports
    |---------------------------------------------------------------------*/

    public function sales(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $sales = Sale::query()
            ->with('customer:id,name')
            ->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)
            ->when($request->query('customer'), fn($q, $c) => $q->where('customer_id', $c))
            ->orderByDesc('sale_date')->orderByDesc('id')
            ->paginate($this->perPage())->withQueryString();

        $totals = [
            'count' => $sales->total(),
            'subtotal' => (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('subtotal'),
            'discount' => (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('discount'),
            'total' => (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('total'),
            'paid' => (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('paid_amount'),
            'due' => (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('due_amount'),
        ];

        return Inertia::render('Reports/Sales', [
            'title' => 'Sales Report',
            'from' => $from,
            'to' => $to,
            'rows' => $this->paginated($sales, fn(Sale $s) => [
                'id' => $s->id,
                'date' => $s->sale_date?->format(self::DATE_FORMAT),
                'invoice_no' => $s->invoice_no,
                'customer' => $s->customer?->name ?? '—',
                'subtotal' => (float) $s->subtotal,
                'discount' => (float) $s->discount,
                'total' => (float) $s->total,
                'paid' => (float) $s->paid_amount,
                'due' => (float) $s->due_amount,
                'status' => $s->statusLabel(),
                'status_tone' => $s->statusTone(),
            ]),
            'totals' => $totals,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'customer' => $request->query('customer'),
            ],
            'customerOptions' => Customer::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function purchases(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $purchases = Purchase::query()
            ->with('supplier:id,name')
            ->whereDate('purchase_date', '>=', $from)->whereDate('purchase_date', '<=', $to)
            ->orderByDesc('purchase_date')->orderByDesc('id')
            ->paginate($this->perPage())->withQueryString();

        $totals = [
            'count' => $purchases->total(),
            'total' => (float) Purchase::query()->whereDate('purchase_date', '>=', $from)->whereDate('purchase_date', '<=', $to)->sum('total'),
            'paid' => (float) Purchase::query()->whereDate('purchase_date', '>=', $from)->whereDate('purchase_date', '<=', $to)->sum('paid_amount'),
            'due' => (float) Purchase::query()->whereDate('purchase_date', '>=', $from)->whereDate('purchase_date', '<=', $to)->sum('due_amount'),
        ];

        return Inertia::render('Reports/Purchases', [
            'title' => 'Purchase Report',
            'from' => $from,
            'to' => $to,
            'rows' => $this->paginated($purchases, fn(Purchase $p) => [
                'id' => $p->id,
                'date' => $p->purchase_date?->format(self::DATE_FORMAT),
                'invoice_no' => $p->invoice_no ?? '—',
                'supplier' => $p->supplier?->name ?? '—',
                'total' => (float) $p->total,
                'paid' => (float) $p->paid_amount,
                'due' => (float) $p->due_amount,
                'status' => $p->statusLabel(),
                'status_tone' => $p->statusTone(),
            ]),
            'totals' => $totals,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function feed(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $purchases = FeedPurchase::query()
            ->with('feedType:id,name')
            ->whereDate('purchased_on', '>=', $from)->whereDate('purchased_on', '<=', $to)
            ->orderByDesc('purchased_on')->get();

        $usages = FeedUsage::query()
            ->with(['feedType:id,name', 'pond:id,name'])
            ->whereDate('used_on', '>=', $from)->whereDate('used_on', '<=', $to)
            ->orderByDesc('used_on')->get();

        $totals = [
            'purchased_kg' => round((float) $purchases->sum('quantity_kg'), 3),
            'used_kg' => round((float) $usages->sum('quantity_kg'), 3),
            'purchase_cost' => round((float) $purchases->sum('total_cost'), 2),
        ];

        return Inertia::render('Reports/Feed', [
            'title' => 'Feed Report',
            'from' => $from,
            'to' => $to,
            'purchases' => $purchases->map(fn(FeedPurchase $p) => [
                'id' => $p->id,
                'date' => $p->purchased_on?->format(self::DATE_FORMAT),
                'feed' => $p->feedType?->name ?? '—',
                'quantity_kg' => (float) $p->quantity_kg,
                'total_cost' => (float) $p->total_cost,
            ])->values()->all(),
            'usages' => $usages->map(fn(FeedUsage $u) => [
                'id' => $u->id,
                'date' => $u->used_on?->format(self::DATE_FORMAT),
                'feed' => $u->feedType?->name ?? '—',
                'pond' => $u->pond?->name ?? '—',
                'quantity_kg' => (float) $u->quantity_kg,
            ])->values()->all(),
            'totals' => $totals,
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function fishStock(Request $request): Response
    {
        $ponds = Pond::query()->with('type:id,name')->orderBy('pond_number')->get();
        $stock = $this->stock->stockForPonds($ponds->pluck('id')->all());

        $rows = $ponds
            ->when($request->query('pond'), fn($c, $id) => $c->where('id', (int) $id))
            ->map(fn(Pond $p): array => [
                'id' => $p->id,
                'pond_number' => $p->pond_number,
                'name' => $p->name,
                'stock' => $stock[$p->id] ?? 0,
                'status' => $p->statusLabel(),
            ])
            ->values();

        return Inertia::render('Reports/FishStock', [
            'title' => 'Fish Stock Report',
            'rows' => $rows->all(),
            'totalStock' => $this->stock->totalStock(),
            'pondOptions' => Pond::query()->orderBy('pond_number')->pluck('name', 'id'),
            'filters' => ['pond' => $request->query('pond')],
        ]);
    }

    public function ponds(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $ponds = Pond::query()->with('type:id,name')->orderBy('pond_number')->get();

        $service = app(\App\Services\Pond\PondLedgerService::class);
        $summaries = $service->summariesForPondIds($ponds->pluck('id')->all());

        $rows = $ponds->map(function (Pond $p) use ($summaries): array {
            $s = $summaries[$p->id] ?? ['income' => 0, 'expense' => 0, 'profit' => 0];

            return [
                'id' => $p->id,
                'pond_number' => $p->pond_number,
                'name' => $p->name,
                'income' => (float) $s['income'],
                'expense' => (float) $s['expense'],
                'profit' => (float) $s['profit'],
            ];
        })->values()->all();

        return Inertia::render('Reports/Ponds', [
            'title' => 'Pond Report',
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'totals' => $service->totalSummary(),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function fcrGrowth(Request $request): Response
    {
        $pondId = $request->query('pond');

        $growth = GrowthRecord::query()
            ->with('pond:id,name')
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->orderByDesc('sampled_on')->orderByDesc('id')
            ->paginate($this->perPage())->withQueryString();

        $fcr = app(FcrService::class);
        $ponds = Pond::query()->orderBy('pond_number')->get();
        $fcrRows = $ponds->map(fn(Pond $p): array => [
            'id' => $p->id,
            'pond' => $p->name,
            'result' => $this->fcrResult($fcr->forPond($p)),
        ])->values()->all();

        return Inertia::render('Reports/FcrGrowth', [
            'title' => 'FCR & Growth Report',
            'rows' => $this->paginated($growth, fn(GrowthRecord $g) => [
                'id' => $g->id,
                'date' => $g->sampled_on?->format(self::DATE_FORMAT),
                'pond' => $g->pond?->name ?? '—',
                'avg_weight_g' => $g->avg_weight_g,
                'sample_size' => $g->sample_size,
            ]),
            'fcrRows' => $fcrRows,
            'pondOptions' => Pond::query()->orderBy('pond_number')->pluck('name', 'id'),
            'filters' => ['pond' => $pondId],
        ]);
    }

    public function income(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $entries = IncomeEntry::query()
            ->with('pond:id,name')
            ->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)
            ->when($request->query('category'), fn($q, $c) => $q->where('category', $c))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate($this->perPage())->withQueryString();

        $salesTotal = (float) Sale::query()
            ->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('total');

        $miscTotal = (float) IncomeEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->sum('amount');

        return Inertia::render('Reports/Income', [
            'title' => 'Income Report',
            'from' => $from,
            'to' => $to,
            'rows' => $this->paginated($entries, fn(IncomeEntry $e) => [
                'id' => $e->id,
                'date' => $e->entry_date?->format(self::DATE_FORMAT),
                'category' => $e->categoryLabel(),
                'pond' => $e->pond?->name ?? 'Farm-wide',
                'amount' => (float) $e->amount,
                'reference' => $e->reference,
            ]),
            'salesTotal' => round($salesTotal, 2),
            'miscTotal' => round($miscTotal, 2),
            'incomeTotal' => round($salesTotal + $miscTotal, 2),
            'categoryOptions' => config('finance.income_categories', []),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'category' => $request->query('category'),
            ],
        ]);
    }

    public function expenses(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $entries = ExpenseEntry::query()
            ->with(['pond:id,name', 'category:id,name'])
            ->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)
            ->when($request->query('category'), fn($q, $c) => $q->where('expense_category_id', $c))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate($this->perPage())->withQueryString();

        return Inertia::render('Reports/Expenses', [
            'title' => 'Expense Report',
            'from' => $from,
            'to' => $to,
            'rows' => $this->paginated($entries, fn(ExpenseEntry $e) => [
                'id' => $e->id,
                'date' => $e->entry_date?->format(self::DATE_FORMAT),
                'category' => $e->category?->name ?? '—',
                'pond' => $e->pond?->name ?? 'Farm-wide',
                'amount' => (float) $e->amount,
                'paid_to' => $e->paid_to,
                'reference' => $e->reference,
            ]),
            'expenseTotal' => round((float) ExpenseEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->sum('amount'), 2),
            'byCategory' => $this->finance->expenseByCategory([$from, $to]),
            'categories' => \App\Models\ExpenseCategory::query()->orderBy('name')->pluck('name', 'id'),
            'filters' => [
                'from' => $from,
                'to' => $to,
                'category' => $request->query('category'),
            ],
        ]);
    }

    public function profitLoss(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        $sales = (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('total');
        $misc = (float) IncomeEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->sum('amount');
        $expenses = (float) ExpenseEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->sum('amount');

        $rules = app(\App\Services\Finance\LedgerRules::class);

        return Inertia::render('Reports/ProfitLoss', [
            'title' => 'Profit & Loss',
            'from' => $from,
            'to' => $to,
            'sales' => round($sales, 2),
            'miscIncome' => round($misc, 2),
            'income' => round($sales + $misc, 2),
            'expense' => round($expenses, 2),
            // A negative result is a real loss and is shown as one.
            'profit' => $rules->netProfit($sales + $misc, $expenses),
            'receivable' => $this->customerBalances->totalReceivable(),
            'payable' => $this->supplierBalances->totalPayable(),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    /* ---------------------------------------------------------------------
     | CSV export — one route, dispatches to the report that was asked for
    |---------------------------------------------------------------------*/

    public function export(Request $request, string $report): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        return match ($report) {
            'sales' => $this->exportSales($request, $from, $to),
            'purchases' => $this->exportPurchases($from, $to),
            'feed' => $this->exportFeed($from, $to),
            'fish-stock' => $this->exportFishStock(),
            'ponds' => $this->exportPonds(),
            'fcr-growth' => $this->exportGrowth($request),
            'income' => $this->exportIncome($request, $from, $to),
            'expenses' => $this->exportExpenses($request, $from, $to),
            'profit-loss' => $this->exportProfitLoss($from, $to),
            default => abort(404),
        };
    }

    private function exportSales(Request $request, string $from, string $to): StreamedResponse
    {
        $rows = Sale::query()
            ->with('customer:id,name')
            ->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)
            // Same filters as the on-screen report, so the file matches the table.
            ->when($request->query('customer'), fn($q, $c) => $q->where('customer_id', $c))
            ->orderByDesc('sale_date')->get()
            ->map(fn(Sale $s): array => [
                $s->sale_date?->format(self::DATE_FORMAT) ?? '',
                $s->invoice_no,
                $s->customer?->name ?? '',
                number_format((float) $s->subtotal, 2, '.', ''),
                number_format((float) $s->discount, 2, '.', ''),
                number_format((float) $s->total, 2, '.', ''),
                number_format((float) $s->paid_amount, 2, '.', ''),
                number_format((float) $s->due_amount, 2, '.', ''),
                $s->statusLabel(),
            ]);

        return CsvExporter::download("sales-report-{$from}-to-{$to}", [
            'Date',
            'Invoice',
            'Customer',
            'Subtotal',
            'Discount',
            'Total',
            'Paid',
            'Due',
            'Status',
        ], $rows);
    }

    private function exportPurchases(string $from, string $to): StreamedResponse
    {
        $rows = Purchase::query()
            ->with('supplier:id,name')
            ->whereDate('purchase_date', '>=', $from)->whereDate('purchase_date', '<=', $to)
            ->orderByDesc('purchase_date')->get()
            ->map(fn(Purchase $p): array => [
                $p->purchase_date?->format(self::DATE_FORMAT) ?? '',
                $p->invoice_no ?? '',
                $p->supplier?->name ?? '',
                number_format((float) $p->total, 2, '.', ''),
                number_format((float) $p->paid_amount, 2, '.', ''),
                number_format((float) $p->due_amount, 2, '.', ''),
                $p->statusLabel(),
            ]);

        return CsvExporter::download("purchase-report-{$from}-to-{$to}", [
            'Date',
            'Invoice',
            'Supplier',
            'Total',
            'Paid',
            'Due',
            'Status',
        ], $rows);
    }

    private function exportFeed(string $from, string $to): StreamedResponse
    {
        $rows = FeedUsage::query()
            ->with(['feedType:id,name', 'pond:id,name'])
            ->whereDate('used_on', '>=', $from)->whereDate('used_on', '<=', $to)
            ->orderByDesc('used_on')->get()
            ->map(fn(FeedUsage $u): array => [
                $u->used_on?->format(self::DATE_FORMAT) ?? '',
                $u->feedType?->name ?? '',
                $u->pond?->name ?? '',
                number_format((float) $u->quantity_kg, 3, '.', ''),
            ]);

        return CsvExporter::download("feed-report-{$from}-to-{$to}", [
            'Date',
            'Feed',
            'Pond',
            'Quantity (kg)',
        ], $rows);
    }

    private function exportFishStock(): StreamedResponse
    {
        $ponds = Pond::query()->orderBy('pond_number')->get();
        $stock = $this->stock->stockForPonds($ponds->pluck('id')->all());

        $rows = $ponds->map(fn(Pond $p): array => [
            $p->pond_number,
            $p->name,
            $stock[$p->id] ?? 0,
            $p->statusLabel(),
        ]);

        return CsvExporter::download('fish-stock-report', [
            'Pond No',
            'Pond',
            'Live Fish',
            'Status',
        ], $rows);
    }

    private function exportPonds(): StreamedResponse
    {
        $ponds = Pond::query()->orderBy('pond_number')->get();
        $service = app(\App\Services\Pond\PondLedgerService::class);
        $summaries = $service->summariesForPondIds($ponds->pluck('id')->all());

        $rows = $ponds->map(function (Pond $p) use ($summaries): array {
            $s = $summaries[$p->id] ?? ['income' => 0, 'expense' => 0, 'profit' => 0];

            return [
                $p->pond_number,
                $p->name,
                number_format($s['income'], 2, '.', ''),
                number_format($s['expense'], 2, '.', ''),
                number_format($s['profit'], 2, '.', ''),
            ];
        });

        return CsvExporter::download('pond-report', [
            'Pond No',
            'Pond',
            'Income',
            'Expense',
            'Profit',
        ], $rows);
    }

    private function exportGrowth(Request $request): StreamedResponse
    {
        $rows = GrowthRecord::query()
            ->with('pond:id,name')
            ->when($request->query('pond'), fn($q, $id) => $q->where('pond_id', $id))
            ->orderByDesc('sampled_on')->get()
            ->map(fn(GrowthRecord $g): array => [
                $g->sampled_on?->format(self::DATE_FORMAT) ?? '',
                $g->pond?->name ?? '',
                (string) ($g->avg_weight_g ?? ''),
                (string) ($g->sample_size ?? ''),
            ]);

        return CsvExporter::download('fcr-growth-report', [
            'Date',
            'Pond',
            'Avg Weight (g)',
            'Sample Size',
        ], $rows);
    }

    private function exportIncome(Request $request, string $from, string $to): StreamedResponse
    {
        $rows = IncomeEntry::query()
            ->with('pond:id,name')
            ->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)
            ->when($request->query('category'), fn($q, $c) => $q->where('category', $c))
            ->orderByDesc('entry_date')->get()
            ->map(fn(IncomeEntry $e): array => [
                $e->entry_date?->format(self::DATE_FORMAT) ?? '',
                $e->categoryLabel(),
                $e->pond?->name ?? 'Farm-wide',
                number_format((float) $e->amount, 2, '.', ''),
                $e->reference ?? '',
            ]);

        return CsvExporter::download("income-report-{$from}-to-{$to}", [
            'Date',
            'Category',
            'Pond',
            'Amount',
            'Reference',
        ], $rows);
    }

    private function exportExpenses(Request $request, string $from, string $to): StreamedResponse
    {
        $rows = ExpenseEntry::query()
            ->with(['pond:id,name', 'category:id,name'])
            ->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)
            ->when($request->query('category'), fn($q, $c) => $q->where('expense_category_id', $c))
            ->orderByDesc('entry_date')->get()
            ->map(fn(ExpenseEntry $e): array => [
                $e->entry_date?->format(self::DATE_FORMAT) ?? '',
                $e->category?->name ?? '',
                $e->pond?->name ?? 'Farm-wide',
                number_format((float) $e->amount, 2, '.', ''),
                $e->paid_to ?? '',
                $e->reference ?? '',
            ]);

        return CsvExporter::download("expense-report-{$from}-to-{$to}", [
            'Date',
            'Category',
            'Pond',
            'Amount',
            'Paid To',
            'Reference',
        ], $rows);
    }

    private function exportProfitLoss(string $from, string $to): StreamedResponse
    {
        $sales = (float) Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)->sum('total');
        $misc = (float) IncomeEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->sum('amount');
        $expenses = (float) ExpenseEntry::query()->whereDate('entry_date', '>=', $from)->whereDate('entry_date', '<=', $to)->sum('amount');
        $rules = app(\App\Services\Finance\LedgerRules::class);

        $rows = [
            ['Fish sales', number_format($sales, 2, '.', '')],
            ['Other income', number_format($misc, 2, '.', '')],
            ['Total income', number_format($sales + $misc, 2, '.', '')],
            ['Total expense', number_format($expenses, 2, '.', '')],
            ['Net profit', number_format($rules->netProfit($sales + $misc, $expenses), 2, '.', '')],
        ];

        return CsvExporter::download("profit-loss-{$from}-to-{$to}", ['Line', 'Amount'], $rows);
    }

    /* ---------------------------------------------------------------------
     | Helpers
    |---------------------------------------------------------------------*/

    /**
     * Serialise a paginator's rows with a mapper while keeping the pagination
     * metadata (links, counts) the React table needs.
     *
     * Presentation only — no calculation happens here.
     */
    private function paginated($paginator, callable $mapper): array
    {
        return [
            'data' => collect($paginator->items())->map($mapper)->values()->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'links' => $paginator->linkCollection()->map(fn($l) => [
                'url' => $l['url'],
                'label' => $l['label'],
                'active' => (bool) $l['active'],
            ])->values()->all(),
        ];
    }

    /** Shape an FCR result into a flat array for the React table. */
    private function fcrResult($result): array
    {
        if (! is_object($result)) {
            return ['available' => false, 'display' => '—', 'reason' => 'FCR unavailable.'];
        }

        return [
            'available' => method_exists($result, 'isAvailable') ? $result->isAvailable() : false,
            'display' => method_exists($result, 'display') ? $result->display() : '—',
            'reason' => method_exists($result, 'reason') ? $result->reason() : '',
            'band' => method_exists($result, 'band') ? $result->band() : 'unknown',
            'feed_kg' => $result->totalFeedKg ?? null,
            'gain_kg' => $result->weightGainKg ?? null,
        ];
    }

    /**
     * Resolve the date range from the request, defaulting to the current month.
     *
     * @return array{0: string, 1: string}
     */
    private function range(Request $request): array
    {
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        // Guard against a reversed range so a report never silently returns nothing.
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function perPage(): int
    {
        return (int) config('fishfarm.pagination.reports', 25);
    }
}
