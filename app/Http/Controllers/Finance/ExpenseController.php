<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\Pond;
use App\Services\Finance\FinanceService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Expense entries — money out.
 *
 * When a pond is chosen, FinanceService also debits that pond's ledger.
 */
class ExpenseController extends Controller
{
    public function __construct(
        private readonly FinanceService $finance,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $categoryId = $request->query('category');
        $pondId = $request->query('pond');
        $from = $request->query('from');
        $to = $request->query('to');

        $entries = ExpenseEntry::query()
            ->with(['pond:id,name,pond_number', 'category:id,name', 'creator:id,name'])
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Finance/Expenses/Index', [
            'title' => 'Expenses',
            'entries' => [
                'data' => collect($entries->items())->map(fn (ExpenseEntry $e): array => [
                    'id' => $e->id,
                    'date' => $e->entry_date?->toDateString(),
                    'category' => $e->category?->name ?? '—',
                    'pond' => $e->pond?->name,
                    'amount' => (float) $e->amount,
                    'paid_to' => $e->paid_to,
                    'reference' => $e->reference,
                    'urls' => [
                        'destroy' => route('finance.expenses.destroy', $e, absolute: false),
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
                'category' => $categoryId, 'pond' => $pondId,
                'from' => $from, 'to' => $to,
            ],
            'options' => [
                'categoryOptions' => $this->categoryOptions()->all(),
                'pondOptions' => $this->pondOptions()->all(),
            ],
            'byCategory' => $this->finance->expenseByCategory([$from, $to]),
            'overview' => $this->finance->overview(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $categoryId = $request->query('category');
        $pondId = $request->query('pond');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = ExpenseEntry::query()
            ->with(['pond:id,name,pond_number', 'category:id,name'])
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->get()
            ->map(fn(ExpenseEntry $e): array => [
                $e->entry_date?->format('Y-m-d') ?? '',
                $e->category?->name ?? '',
                $e->pond?->name ?? 'Farm-wide',
                number_format((float) $e->amount, 2, '.', ''),
                $e->paid_to ?? '',
                $e->reference ?? '',
            ]);

        return CsvExporter::download('expenses', [
            'Date',
            'Category',
            'Pond',
            'Amount',
            'Paid To',
            'Reference',
        ], $rows);
    }

    public function create(): \Inertia\Response
    {
        Gate::authorize('create', ExpenseEntry::class);

        return \Inertia\Inertia::render('Finance/Expenses/Create', [
            'title' => 'Record Expense',
            'options' => [
                'categoryOptions' => $this->categoryOptions()->all(),
                'pondOptions' => $this->pondOptions()->all(),
            ],
            'hasCategories' => ExpenseCategory::query()->exists(),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->finance->actorId();

        $entry = $this->finance->createExpense($data);

        return AsyncResponse::ok(
            $request,
            'Expense of ' . number_format((float) $entry->amount, 2) . ' recorded.',
            'finance.expenses.index',
        );
    }

    public function destroy(Request $request, ExpenseEntry $entry): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $entry);

        $this->finance->deleteExpense($entry);

        return AsyncResponse::ok($request, 'Expense entry deleted.', 'finance.expenses.index');
    }

    /** @return Collection<int, string> */
    private function categoryOptions(): Collection
    {
        return ExpenseCategory::query()->orderBy('name')->pluck('name', 'id');
    }

    /** @return Collection<int, string> */
    private function pondOptions(): Collection
    {
        return Pond::query()->orderBy('pond_number')->get(['id', 'pond_number', 'name'])
            ->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"]);
    }
}
