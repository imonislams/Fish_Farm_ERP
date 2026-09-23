<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StorePurchaseRequest;
use App\Models\FeedType;
use App\Models\FishSpecies;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\Supplier\PurchaseService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Purchases from suppliers.
 *
 * Thin controller. A purchase's header + items + derived totals are written by
 * PurchaseService inside one transaction.
 */
class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseService $purchases,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $supplierId = $request->query('supplier');
        $status = (string) $request->query('status', '');
        $from = $request->query('from');
        $to = $request->query('to');

        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'creator:id,name'])
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->whereDate('purchase_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('purchase_date', '<=', $to))
            ->orderByDesc('purchase_date')->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Suppliers/Purchases/Index', [
            'title' => 'Supplier Purchases',
            'purchases' => [
                'data' => collect($purchases->items())->map(fn (Purchase $p): array => [
                    'id' => $p->id,
                    'date' => $p->purchase_date?->toDateString(),
                    'invoice_no' => $p->invoice_no,
                    'supplier' => $p->supplier?->name,
                    'supplier_id' => $p->supplier_id,
                    'total' => (float) $p->total,
                    'paid' => (float) $p->paid_amount,
                    'due' => (float) $p->due_amount,
                    'status' => $p->statusLabel(),
                    'status_tone' => $p->statusTone(),
                    'urls' => [
                        'edit' => route('suppliers.purchases.edit', $p, absolute: false),
                        'destroy' => route('suppliers.purchases.destroy', $p, absolute: false),
                    ],
                ])->all(),
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'total' => $purchases->total(),
                'from' => $purchases->firstItem(),
                'to' => $purchases->lastItem(),
                'links' => $purchases->linkCollection()->toArray(),
            ],
            'filters' => ['supplier' => $supplierId, 'status' => $status, 'from' => $from, 'to' => $to],
            'options' => [
                'supplierOptions' => $this->supplierOptions()->all(),
                'statusOptions' => $this->statusOptions(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $supplierId = $request->query('supplier');
        $status = (string) $request->query('status', '');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = Purchase::query()
            ->with('supplier:id,name')
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->whereDate('purchase_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('purchase_date', '<=', $to))
            ->orderByDesc('purchase_date')->orderByDesc('id')
            ->get()
            ->map(fn (Purchase $p): array => [
                $p->purchase_date?->format('Y-m-d') ?? '',
                $p->invoice_no ?? '',
                $p->supplier?->name ?? '',
                number_format((float) $p->subtotal, 2, '.', ''),
                number_format((float) $p->discount, 2, '.', ''),
                number_format((float) $p->total, 2, '.', ''),
                number_format((float) $p->paid_amount, 2, '.', ''),
                number_format((float) $p->due_amount, 2, '.', ''),
                $p->statusLabel(),
            ]);

        return CsvExporter::download('purchases', [
            'Date', 'Invoice', 'Supplier', 'Subtotal', 'Discount', 'Total', 'Paid', 'Due', 'Status',
        ], $rows);
    }

    public function create(): \Inertia\Response
    {
        Gate::authorize('create', Purchase::class);

        return \Inertia\Inertia::render('Suppliers/Purchases/Create', [
            'title' => 'New Purchase',
            'options' => $this->formOptions(),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $purchase = $this->purchases->create($data);

        return AsyncResponse::ok(
            $request,
            'Purchase from "' . $purchase->supplier->name . '" recorded.',
            'suppliers.purchases.index',
        );
    }

    public function edit(Purchase $purchase): \Inertia\Response
    {
        Gate::authorize('update', $purchase);

        $purchase->load('items');

        return \Inertia\Inertia::render('Suppliers/Purchases/Edit', [
            'title' => 'Edit Purchase',
            'purchase' => [
                'id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'invoice_no' => $purchase->invoice_no,
                'purchase_date' => $purchase->purchase_date?->toDateString(),
                'discount' => $purchase->discount,
                'paid_amount' => $purchase->paid_amount,
                'note' => $purchase->note,
                'items' => $purchase->items->map(fn ($i): array => [
                    'item_type' => $i->item_type,
                    'feed_type_id' => $i->feed_type_id,
                    'fish_species_id' => $i->fish_species_id,
                    'description' => $i->description,
                    'quantity' => $i->quantity,
                    'unit_cost' => $i->unit_cost,
                ])->values()->all(),
            ],
            'options' => $this->formOptions(),
        ]);
    }

    /**
     * The option lists the create/edit forms need.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'supplierOptions' => $this->supplierOptions()->all(),
            'itemTypeOptions' => config('finance.purchase_item_types', []),
            'feedTypeOptions' => $this->feedTypeOptions()->all(),
            'speciesOptions' => $this->speciesOptions()->all(),
        ];
    }

    public function update(StorePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        Gate::authorize('update', $purchase);

        $this->purchases->update($purchase, $request->validated());

        return AsyncResponse::ok($request, 'Purchase updated.', 'suppliers.purchases.index');
    }

    public function destroy(Request $request, Purchase $purchase): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $purchase);

        $this->purchases->delete($purchase);

        return AsyncResponse::ok($request, 'Purchase deleted.', 'suppliers.purchases.index');
    }

    /** @return Collection<int, string> */
    private function supplierOptions(): Collection
    {
        return Supplier::query()->orderBy('name')->pluck('name', 'id');
    }

    /** @return Collection<int, string> */
    private function feedTypeOptions(): Collection
    {
        return FeedType::query()->orderBy('name')->get(['id', 'name', 'brand'])
            ->mapWithKeys(fn (FeedType $t) => [$t->id => $t->displayName()]);
    }

    /** @return Collection<int, string> */
    private function speciesOptions(): Collection
    {
        return FishSpecies::query()->orderBy('name')->pluck('name', 'id');
    }

    /** @return array<string, string> */
    private function statusOptions(): array
    {
        return collect(config('finance.settlement_statuses', []))
            ->map(fn (array $meta): string => $meta['label'])
            ->all();
    }
}