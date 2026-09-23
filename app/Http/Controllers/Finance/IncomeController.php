<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreIncomeRequest;
use App\Models\IncomeEntry;
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
 * Income entries — money in that is NOT a fish sale.
 *
 * When a pond is chosen, FinanceService also credits that pond's ledger, so
 * per-pond profitability includes the income.
 */
class IncomeController extends Controller
{
    public function __construct(
        private readonly FinanceService $finance,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $category = (string) $request->query('category', '');
        $pondId = $request->query('pond');
        $from = $request->query('from');
        $to = $request->query('to');

        $entries = IncomeEntry::query()
            ->with(['pond:id,name,pond_number', 'creator:id,name'])
            ->when($category !== '', fn($q) => $q->where('category', $category))
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Finance/Income/Index', [
            'title' => 'Income',
            'entries' => [
                'data' => collect($entries->items())->map(fn (IncomeEntry $e): array => [
                    'id' => $e->id,
                    'date' => $e->entry_date?->toDateString(),
                    'category' => $e->categoryLabel(),
                    'pond' => $e->pond?->name,
                    'amount' => (float) $e->amount,
                    'reference' => $e->reference,
                    'urls' => [
                        'destroy' => route('finance.income.destroy', $e, absolute: false),
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
                'category' => $category, 'pond' => $pondId,
                'from' => $from, 'to' => $to,
            ],
            'options' => [
                'categoryOptions' => $this->categoryOptions(),
                'pondOptions' => $this->pondOptions()->all(),
            ],
            'byCategory' => $this->finance->incomeByCategory([$from, $to]),
            'overview' => $this->finance->overview(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $category = (string) $request->query('category', '');
        $pondId = $request->query('pond');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = IncomeEntry::query()
            ->with('pond:id,name,pond_number')
            ->when($category !== '', fn($q) => $q->where('category', $category))
            ->when($pondId, fn($q) => $q->where('pond_id', $pondId))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->get()
            ->map(fn(IncomeEntry $e): array => [
                $e->entry_date?->format('Y-m-d') ?? '',
                $e->categoryLabel(),
                $e->pond?->name ?? 'Farm-wide',
                number_format((float) $e->amount, 2, '.', ''),
                $e->reference ?? '',
                $e->note ?? '',
            ]);

        return CsvExporter::download('income', [
            'Date',
            'Category',
            'Pond',
            'Amount',
            'Reference',
            'Note',
        ], $rows);
    }

    public function create(): \Inertia\Response
    {
        Gate::authorize('create', IncomeEntry::class);

        return \Inertia\Inertia::render('Finance/Income/Create', [
            'title' => 'Record Income',
            'options' => [
                'categoryOptions' => $this->categoryOptions(),
                'pondOptions' => $this->pondOptions()->all(),
            ],
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $this->finance->actorId();

        $entry = $this->finance->createIncome($data);

        return AsyncResponse::ok(
            $request,
            'Income of ' . number_format((float) $entry->amount, 2) . ' recorded.',
            'finance.income.index',
        );
    }

    public function destroy(Request $request, IncomeEntry $entry): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $entry);

        $this->finance->deleteIncome($entry);

        return AsyncResponse::ok($request, 'Income entry deleted.', 'finance.income.index');
    }

    /** @return array<string, string> */
    private function categoryOptions(): array
    {
        return config('finance.income_categories', []);
    }

    /** @return Collection<int, string> */
    private function pondOptions(): Collection
    {
        return Pond::query()->orderBy('pond_number')->get(['id', 'pond_number', 'name'])
            ->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"]);
    }
}
