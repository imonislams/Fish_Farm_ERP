<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SaveCustomerRequest;
use App\Models\Customer;
use App\Services\Finance\CustomerBalanceService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customer management.
 *
 * Thin controller: authorize, validate (FormRequest), delegate to
 * CustomerBalanceService for derived figures and guards, then respond through
 * AsyncResponse (redirect normally, JSON for the async layer).
 *
 * The customer DUE is never stored — it is derived by the service from sales and
 * payments (docs/BUSINESS_LOGIC.md §3).
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerBalanceService $balances,
    ) {}

    /** Paginated, searchable customer list with their live dues (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $customers = Customer::query()
            ->withCount(['sales', 'payments'])   // feeds isDeletable() without a query per row
            ->search($search)
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        // One batch computation for the whole page — no N+1.
        $dues = $this->balances->duesForCustomers($customers->pluck('id')->all());

        return \Inertia\Inertia::render('Customers/Index', [
            'title' => 'Customers',
            'customers' => [
                'data' => collect($customers->items())->map(fn (Customer $c): array => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'email' => $c->email,
                    'address' => $c->address,
                    'opening' => (float) $c->opening_balance,
                    'due' => $dues[$c->id] ?? 0.0,
                    'is_active' => (bool) $c->is_active,
                    'deletable' => $c->isDeletable(),
                    'urls' => [
                        'edit' => route('customers.edit', $c, absolute: false),
                        'destroy' => route('customers.destroy', $c, absolute: false),
                    ],
                ])->all(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
                'links' => $customers->linkCollection()->toArray(),
            ],
            'filters' => ['search' => $search, 'status' => $status],
            'summary' => [
                'totalReceivable' => $this->balances->totalReceivable(),
                'activeCount' => Customer::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    /** Export the (filtered) customer list with dues as CSV. */
    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $customers = Customer::query()
            ->search($search)
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->get();

        $dues = $this->balances->duesForCustomers($customers->pluck('id')->all());

        $rows = $customers->map(fn(Customer $c): array => [
            $c->name,
            $c->phone ?? '',
            $c->email ?? '',
            $c->address ?? '',
            number_format((float) $c->opening_balance, 2, '.', ''),
            number_format($dues[$c->id] ?? 0, 2, '.', ''),
            $c->is_active ? 'Active' : 'Inactive',
        ]);

        return CsvExporter::download('customers', [
            'Name',
            'Phone',
            'Email',
            'Address',
            'Opening Balance',
            'Due',
            'Status',
        ], $rows);
    }

    public function create(): \Inertia\Response
    {
        Gate::authorize('create', Customer::class);

        return \Inertia\Inertia::render('Customers/Create', ['title' => 'New Customer']);
    }

    public function store(SaveCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return AsyncResponse::ok($request, "Customer \"{$customer->name}\" created.", 'customers.index');
    }

    public function edit(Customer $customer): \Inertia\Response
    {
        Gate::authorize('update', $customer);

        return \Inertia\Inertia::render('Customers/Edit', [
            'title' => 'Edit — ' . $customer->name,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'opening_balance' => $customer->opening_balance,
                'credit_limit' => $customer->credit_limit,
                'note' => $customer->note,
                'is_active' => (bool) $customer->is_active,
                'deletable' => $customer->isDeletable(),
                'sales_count' => $customer->sales()->count(),
                'payments_count' => $customer->payments()->count(),
            ],
            'due' => $this->balances->due($customer),
        ]);
    }

    public function update(SaveCustomerRequest $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('update', $customer);

        $customer->update($request->validated());

        return AsyncResponse::ok($request, "Customer \"{$customer->name}\" updated.", 'customers.index');
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $customer);

        $name = $customer->name;

        try {
            $this->balances->guardDeletable($customer);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        $customer->delete();

        return AsyncResponse::ok($request, "Customer \"{$name}\" deleted.", 'customers.index');
    }
}
