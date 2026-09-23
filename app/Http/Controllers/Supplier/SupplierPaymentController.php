<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierPaymentRequest;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Finance\SupplierBalanceService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Supplier payments (money paid out).
 *
 * Recording a payment also recalculates the linked purchase's paid/due/status in
 * the SAME transaction.
 */
class SupplierPaymentController extends Controller
{
    public function __construct(
        private readonly SupplierBalanceService $balances,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $supplierId = $request->query('supplier');
        $from = $request->query('from');
        $to = $request->query('to');

        $payments = SupplierPayment::query()
            ->with(['supplier:id,name', 'purchase:id,invoice_no', 'creator:id,name'])
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
            ->when($from, fn($q) => $q->whereDate('paid_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('paid_on', '<=', $to))
            ->orderByDesc('paid_on')->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Suppliers/Payments/Index', [
            'title' => 'Supplier Payments',
            'payments' => [
                'data' => collect($payments->items())->map(fn (SupplierPayment $p): array => [
                    'id' => $p->id,
                    'date' => $p->paid_on?->toDateString(),
                    'supplier' => $p->supplier?->name,
                    'invoice' => $p->purchase?->invoice_no,
                    'method' => $p->methodLabel(),
                    'amount' => (float) $p->amount,
                    'reference' => $p->reference,
                    'urls' => [
                        'destroy' => route('suppliers.payments.destroy', $p, absolute: false),
                    ],
                ])->all(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
                'links' => $payments->linkCollection()->toArray(),
            ],
            'filters' => ['supplier' => $supplierId, 'from' => $from, 'to' => $to],
            'options' => [
                'supplierOptions' => $this->supplierOptions()->all(),
                'methodOptions' => $this->methodOptions(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $supplierId = $request->query('supplier');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = SupplierPayment::query()
            ->with(['supplier:id,name', 'purchase:id,invoice_no'])
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
            ->when($from, fn($q) => $q->whereDate('paid_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('paid_on', '<=', $to))
            ->orderByDesc('paid_on')->orderByDesc('id')
            ->get()
            ->map(fn(SupplierPayment $p): array => [
                $p->paid_on?->format('Y-m-d') ?? '',
                $p->supplier?->name ?? '',
                $p->purchase?->invoice_no ?? '',
                number_format((float) $p->amount, 2, '.', ''),
                $p->methodLabel(),
                $p->reference ?? '',
            ]);

        return CsvExporter::download('supplier-payments', [
            'Date',
            'Supplier',
            'Invoice',
            'Amount',
            'Method',
            'Reference',
        ], $rows);
    }

    public function create(Request $request): \Inertia\Response
    {
        Gate::authorize('create', SupplierPayment::class);

        return \Inertia\Inertia::render('Suppliers/Payments/Create', [
            'title' => 'Record Supplier Payment',
            'options' => [
                'supplierOptions' => $this->supplierOptions()->all(),
                'methodOptions' => $this->methodOptions(),
            ],
            'selectedSupplierId' => $request->query('supplier') ? (int) $request->query('supplier') : null,
            'dueBySupplier' => $this->balances->duesForSuppliers(
                Supplier::query()->pluck('id')->all()
            ),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreSupplierPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $payment = DB::transaction(function () use ($data): SupplierPayment {
            $payment = SupplierPayment::create($data);

            if ($payment->purchase_id) {
                $purchase = Purchase::find($payment->purchase_id);
                if ($purchase) {
                    $this->balances->syncPurchase($purchase);
                }
            }

            return $payment;
        });

        return AsyncResponse::ok(
            $request,
            'Payment of ' . number_format((float) $payment->amount, 2) . ' recorded.',
            'suppliers.payments.index',
        );
    }

    public function destroy(Request $request, SupplierPayment $payment): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $payment);

        DB::transaction(function () use ($payment): void {
            $purchaseId = $payment->purchase_id;
            $payment->delete();

            if ($purchaseId) {
                $purchase = Purchase::find($purchaseId);
                if ($purchase) {
                    $this->balances->syncPurchase($purchase);
                }
            }
        });

        return AsyncResponse::ok($request, 'Payment deleted.', 'suppliers.payments.index');
    }

    /** @return Collection<int, string> */
    private function supplierOptions(): Collection
    {
        return Supplier::query()->orderBy('name')->pluck('name', 'id');
    }

    /** @return array<string, string> */
    private function methodOptions(): array
    {
        return collect(config('finance.payment_methods', []))
            ->map(fn(array $meta): string => $meta['label'])
            ->all();
    }
}
