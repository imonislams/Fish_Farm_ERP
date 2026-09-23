<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Models\Customer;
use App\Models\FishSpecies;
use App\Models\Pond;
use App\Models\Sale;
use App\Services\Finance\FinanceService;
use App\Services\Sales\SalesService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales — fish sold to customers.
 *
 * Thin controller. Creating/updating a sale writes its header, items AND the pond
 * ledger credits inside one transaction — all inside SalesService, never here.
 */
class SaleController extends Controller
{
    public function __construct(
        private readonly SalesService $sales,
        private readonly FinanceService $finance,
    ) {}

    /** Paginated, filterable sale list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $customerId = $request->query('customer');
        $status = (string) $request->query('status', '');
        $from = $request->query('from');
        $to = $request->query('to');
        $search = trim((string) $request->query('search', ''));

        $sales = Sale::query()
            ->with(['customer:id,name', 'creator:id,name'])
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($search !== '', fn($q) => $q->where('invoice_no', 'like', '%' . $search . '%'))
            ->when($from, fn($q) => $q->whereDate('sale_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sale_date', '<=', $to))
            ->orderByDesc('sale_date')->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Sales/Index', [
            'title' => 'Fish Sales',
            'sales' => [
                'data' => collect($sales->items())->map(fn (Sale $s): array => [
                    'id' => $s->id,
                    'date' => $s->sale_date?->toDateString(),
                    'invoice_no' => $s->invoice_no,
                    'customer' => $s->customer?->name,
                    'total' => (float) $s->total,
                    'paid' => (float) $s->paid_amount,
                    'due' => (float) $s->due_amount,
                    'status' => $s->statusLabel(),
                    'status_tone' => $s->statusTone(),
                    'urls' => [
                        'show' => route('sales.show', $s, absolute: false),
                        'edit' => route('sales.edit', $s, absolute: false),
                        'destroy' => route('sales.destroy', $s, absolute: false),
                    ],
                ])->all(),
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'total' => $sales->total(),
                'from' => $sales->firstItem(),
                'to' => $sales->lastItem(),
                'links' => $sales->linkCollection()->toArray(),
            ],
            'filters' => [
                'customer' => $customerId, 'status' => $status,
                'search' => $search, 'from' => $from, 'to' => $to,
            ],
            'options' => [
                'customerOptions' => $this->customerOptions()->all(),
                'statusOptions' => $this->statusOptions(),
            ],
        ]);
    }

    /** The sales dashboard (summary + recent) — Inertia/React. */
    public function dashboard(Request $request): \Inertia\Response
    {
        $recent = Sale::query()
            ->with('customer:id,name')
            ->orderByDesc('sale_date')->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (Sale $s): array => [
                'id' => $s->id,
                'date' => $s->sale_date?->toDateString(),
                'invoice_no' => $s->invoice_no,
                'customer' => $s->customer?->name,
                'total' => (float) $s->total,
                'due' => (float) $s->due_amount,
                'status' => $s->statusLabel(),
                'status_tone' => $s->statusTone(),
                'urls' => [
                    'show' => route('sales.show', $s, absolute: false),
                ],
            ])
            ->all();

        return \Inertia\Inertia::render('Sales/Dashboard', [
            'title' => 'Sales Dashboard',
            'overview' => $this->finance->overview(),
            'recent' => $recent,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $customerId = $request->query('customer');
        $status = (string) $request->query('status', '');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = Sale::query()
            ->with('customer:id,name')
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($from, fn($q) => $q->whereDate('sale_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('sale_date', '<=', $to))
            ->orderByDesc('sale_date')->orderByDesc('id')
            ->get()
            ->map(fn(Sale $s): array => [
                $s->sale_date?->format('Y-m-d') ?? '',
                $s->invoice_no,
                $s->customer?->name ?? '',
                number_format((float) $s->subtotal, 2, '.', ''),
                number_format((float) $s->discount, 2, '.', ''),
                number_format((float) $s->total, 2, '.', ''),
                number_format((float) $s->paid_amount, 2, '.', ''),
                number_format((float) $s->due_amount, 2, '.', ''),
                $s->statusLabel(),
            ]);

        return CsvExporter::download('sales', [
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

    public function create(): \Inertia\Response
    {
        Gate::authorize('create', Sale::class);

        return \Inertia\Inertia::render('Sales/Create', [
            'title' => 'New Sale',
            'options' => [
                'customerOptions' => $this->customerOptions()->all(),
                'speciesOptions' => $this->speciesOptions()->all(),
                'pondOptions' => $this->pondOptions()->all(),
            ],
            'statusOptions' => $this->statusOptions(),
            'defaultDate' => now()->toDateString(),
            'nextInvoiceNo' => $this->sales->nextInvoiceNo(),
        ]);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $sale = $this->sales->create($data);

        return AsyncResponse::ok(
            $request,
            "Sale {$sale->invoice_no} recorded for \"{$sale->customer->name}\".",
            'sales.index',
        );
    }

    public function show(Sale $sale): \Inertia\Response
    {
        Gate::authorize('view', $sale);

        $sale->load(['customer', 'items.species:id,name', 'items.pond:id,name,pond_number', 'payments']);

        return \Inertia\Inertia::render('Sales/Show', [
            'title' => 'Sale ' . $sale->invoice_no,
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'customer' => $sale->customer?->name,
                'customer_id' => $sale->customer_id,
                'sale_date' => $sale->sale_date?->toDateString(),
                'subtotal' => (float) $sale->subtotal,
                'discount' => (float) $sale->discount,
                'total' => (float) $sale->total,
                'paid' => (float) $sale->paid_amount,
                'due' => (float) $sale->due_amount,
                'status' => $sale->statusLabel(),
                'status_tone' => $sale->statusTone(),
                'note' => $sale->note,
                'items' => $sale->items->map(fn ($i): array => [
                    'id' => $i->id,
                    'label' => $i->label(),
                    'pond' => $i->pond?->name,
                    'weight' => $i->weight_kg !== null
                        ? rtrim(rtrim(number_format((float) $i->weight_kg, 3, '.', ''), '0'), '.') . ' kg'
                        : null,
                    'quantity' => $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                    'line_total' => (float) $i->line_total,
                ])->values()->all(),
                'payments' => $sale->payments->map(fn ($p): array => [
                    'id' => $p->id,
                    'date' => $p->paid_on?->toDateString(),
                    'method' => $p->methodLabel(),
                    'amount' => (float) $p->amount,
                    'reference' => $p->reference,
                ])->values()->all(),
            ],
        ]);
    }

    public function edit(Sale $sale): \Inertia\Response
    {
        Gate::authorize('update', $sale);

        $sale->load('items');

        return \Inertia\Inertia::render('Sales/Edit', [
            'title' => 'Edit — ' . $sale->invoice_no,
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'customer_id' => $sale->customer_id,
                'sale_date' => $sale->sale_date?->toDateString(),
                'discount' => $sale->discount,
                'paid_amount' => $sale->paid_amount,
                'note' => $sale->note,
                'items' => $sale->items->map(fn ($i): array => [
                    'fish_species_id' => $i->fish_species_id,
                    'pond_id' => $i->pond_id,
                    'quantity' => $i->quantity,
                    'weight_kg' => $i->weight_kg,
                    'unit_price' => $i->unit_price,
                    'description' => $i->description,
                ])->values()->all(),
            ],
            'options' => [
                'customerOptions' => $this->customerOptions()->all(),
                'speciesOptions' => $this->speciesOptions()->all(),
                'pondOptions' => $this->pondOptions()->all(),
            ],
        ]);
    }

    public function update(StoreSaleRequest $request, Sale $sale): RedirectResponse
    {
        Gate::authorize('update', $sale);

        $this->sales->update($sale, $request->validated());

        return AsyncResponse::ok($request, "Sale {$sale->invoice_no} updated.", 'sales.index');
    }

    public function destroy(Request $request, Sale $sale): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $sale);

        $invoice = $sale->invoice_no;

        $this->sales->delete($sale);

        return AsyncResponse::ok($request, "Sale {$invoice} deleted.", 'sales.index');
    }

    /** @return Collection<int, string> */
    private function customerOptions(): Collection
    {
        return Customer::query()->orderBy('name')->pluck('name', 'id');
    }

    /** @return Collection<int, string> */
    private function speciesOptions(): Collection
    {
        return FishSpecies::query()->active()->orderBy('name')->pluck('name', 'id');
    }

    /** @return Collection<int, string> */
    private function pondOptions(): Collection
    {
        return Pond::query()->orderBy('pond_number')->get(['id', 'pond_number', 'name'])
            ->mapWithKeys(fn(Pond $p) => [$p->id => "{$p->pond_number} — {$p->name}"]);
    }

    /** @return array<string, string> */
    private function statusOptions(): array
    {
        return collect(config('finance.settlement_statuses', []))
            ->map(fn(array $meta): string => $meta['label'])
            ->all();
    }
}
