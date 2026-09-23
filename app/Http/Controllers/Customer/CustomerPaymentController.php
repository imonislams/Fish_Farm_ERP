<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerPaymentRequest;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Services\Finance\CustomerBalanceService;
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
 * Customer payments (money received).
 *
 * Recording a payment also recalculates the settled sale's paid/due/status in the
 * SAME transaction, so a sale's state always matches the money actually received.
 */
class CustomerPaymentController extends Controller
{
    public function __construct(
        private readonly CustomerBalanceService $balances,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $customerId = $request->query('customer');
        $from = $request->query('from');
        $to = $request->query('to');

        $payments = CustomerPayment::query()
            ->with(['customer:id,name', 'sale:id,invoice_no', 'creator:id,name'])
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($from, fn($q) => $q->whereDate('paid_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('paid_on', '<=', $to))
            ->orderByDesc('paid_on')->orderByDesc('id')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Customers/Payments/Index', [
            'title' => 'Customer Payments',
            'payments' => [
                'data' => collect($payments->items())->map(fn (CustomerPayment $p): array => [
                    'id' => $p->id,
                    'date' => $p->paid_on?->toDateString(),
                    'customer' => $p->customer?->name,
                    'invoice' => $p->sale?->invoice_no,
                    'method' => $p->methodLabel(),
                    'amount' => (float) $p->amount,
                    'reference' => $p->reference,
                    'urls' => [
                        'destroy' => route('customers.payments.destroy', $p, absolute: false),
                    ],
                ])->all(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
                'links' => $payments->linkCollection()->toArray(),
            ],
            'filters' => ['customer' => $customerId, 'from' => $from, 'to' => $to],
            'options' => ['customerOptions' => $this->customerOptions()->all()],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $customerId = $request->query('customer');
        $from = $request->query('from');
        $to = $request->query('to');

        $rows = CustomerPayment::query()
            ->with(['customer:id,name', 'sale:id,invoice_no'])
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($from, fn($q) => $q->whereDate('paid_on', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('paid_on', '<=', $to))
            ->orderByDesc('paid_on')->orderByDesc('id')
            ->get()
            ->map(fn(CustomerPayment $p): array => [
                $p->paid_on?->format('Y-m-d') ?? '',
                $p->customer?->name ?? '',
                $p->sale?->invoice_no ?? '',
                number_format((float) $p->amount, 2, '.', ''),
                $p->methodLabel(),
                $p->reference ?? '',
            ]);

        return CsvExporter::download('customer-payments', [
            'Date',
            'Customer',
            'Invoice',
            'Amount',
            'Method',
            'Reference',
        ], $rows);
    }

    public function create(Request $request): \Inertia\Response
    {
        Gate::authorize('create', CustomerPayment::class);

        $customerId = $request->query('customer');

        return \Inertia\Inertia::render('Customers/Payments/Create', [
            'title' => 'Record Customer Payment',
            'options' => [
                'customerOptions' => $this->customerOptions()->all(),
                'methodOptions' => $this->methodOptions(),
            ],
            'selectedCustomerId' => $customerId ? (int) $customerId : null,
            'dueByCustomer' => $this->balances->duesForCustomers(
                Customer::query()->pluck('id')->all()
            ),
            'defaultDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreCustomerPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $payment = DB::transaction(function () use ($data): CustomerPayment {
            $payment = CustomerPayment::create($data);

            // Keep the linked sale's settlement state in step with the money.
            if ($payment->sale_id) {
                $sale = Sale::find($payment->sale_id);
                if ($sale) {
                    $this->balances->syncSale($sale);
                }
            }

            return $payment;
        });

        return AsyncResponse::ok(
            $request,
            'Payment of ' . number_format((float) $payment->amount, 2) . ' recorded.',
            'customers.payments.index',
        );
    }

    public function destroy(Request $request, CustomerPayment $payment): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $payment);

        DB::transaction(function () use ($payment): void {
            $saleId = $payment->sale_id;
            $payment->delete();

            if ($saleId) {
                $sale = Sale::find($saleId);
                if ($sale) {
                    $this->balances->syncSale($sale);
                }
            }
        });

        return AsyncResponse::ok($request, 'Payment deleted.', 'customers.payments.index');
    }

    /** @return Collection<int, string> */
    private function customerOptions(): Collection
    {
        return Customer::query()->orderBy('name')->pluck('name', 'id');
    }

    /** @return array<string, string> */
    private function methodOptions(): array
    {
        return collect(config('finance.payment_methods', []))
            ->map(fn(array $meta): string => $meta['label'])
            ->all();
    }
}
