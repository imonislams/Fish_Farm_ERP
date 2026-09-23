<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\SaveSupplierRequest;
use App\Models\Supplier;
use App\Services\Finance\SupplierBalanceService;
use App\Support\AsyncResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Supplier management.
 *
 * The supplier DUE is derived by SupplierBalanceService from purchases and
 * payments — never stored (docs/BUSINESS_LOGIC.md §3).
 */
class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierBalanceService $balances,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $suppliers = Supplier::query()
            ->withCount(['purchases', 'payments'])   // feeds isDeletable() without a query per row
            ->search($search)
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        $dues = $this->balances->duesForSuppliers($suppliers->pluck('id')->all());

        return \Inertia\Inertia::render('Suppliers/Index', [
            'title' => 'Suppliers',
            'suppliers' => [
                'data' => collect($suppliers->items())->map(fn (Supplier $s): array => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'phone' => $s->phone,
                    'email' => $s->email,
                    'address' => $s->address,
                    'opening' => (float) $s->opening_balance,
                    'due' => $dues[$s->id] ?? 0.0,
                    'is_active' => (bool) $s->is_active,
                    'deletable' => $s->purchases()->count() === 0 && $s->payments()->count() === 0,
                    'urls' => [
                        'edit' => route('suppliers.edit', $s, absolute: false),
                        'destroy' => route('suppliers.destroy', $s, absolute: false),
                    ],
                ])->all(),
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'total' => $suppliers->total(),
                'from' => $suppliers->firstItem(),
                'to' => $suppliers->lastItem(),
                'links' => $suppliers->linkCollection()->toArray(),
            ],
            'filters' => ['search' => $search, 'status' => $status],
            'summary' => [
                'totalPayable' => $this->balances->totalPayable(),
                'activeCount' => Supplier::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $suppliers = Supplier::query()
            ->search($search)
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->get();

        $dues = $this->balances->duesForSuppliers($suppliers->pluck('id')->all());

        $rows = $suppliers->map(fn(Supplier $s): array => [
            $s->name,
            $s->phone ?? '',
            $s->email ?? '',
            $s->address ?? '',
            number_format((float) $s->opening_balance, 2, '.', ''),
            number_format($dues[$s->id] ?? 0, 2, '.', ''),
            $s->is_active ? 'Active' : 'Inactive',
        ]);

        return CsvExporter::download('suppliers', [
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
        Gate::authorize('create', Supplier::class);

        return \Inertia\Inertia::render('Suppliers/Create', ['title' => 'New Supplier']);
    }

    public function store(SaveSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return AsyncResponse::ok($request, "Supplier \"{$supplier->name}\" created.", 'suppliers.index');
    }

    public function edit(Supplier $supplier): \Inertia\Response
    {
        Gate::authorize('update', $supplier);

        return \Inertia\Inertia::render('Suppliers/Edit', [
            'title' => 'Edit — ' . $supplier->name,
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'opening_balance' => $supplier->opening_balance,
                'note' => $supplier->note,
                'is_active' => (bool) $supplier->is_active,
                'deletable' => $supplier->purchases()->count() === 0 && $supplier->payments()->count() === 0,
                'purchases_count' => $supplier->purchases()->count(),
                'payments_count' => $supplier->payments()->count(),
            ],
            'due' => $this->balances->due($supplier),
        ]);
    }

    public function update(SaveSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        $supplier->update($request->validated());

        return AsyncResponse::ok($request, "Supplier \"{$supplier->name}\" updated.", 'suppliers.index');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $supplier);

        $name = $supplier->name;

        try {
            $this->balances->guardDeletable($supplier);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        $supplier->delete();

        return AsyncResponse::ok($request, "Supplier \"{$name}\" deleted.", 'suppliers.index');
    }
}
