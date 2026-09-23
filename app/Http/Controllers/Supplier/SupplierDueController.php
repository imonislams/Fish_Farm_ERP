<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Finance\SupplierBalanceService;
use App\Support\CsvExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Supplier dues — what the farm owes its suppliers.
 *
 * Derived by SupplierBalanceService from real purchases and payments. A credit
 * (negative due) is shown honestly rather than hidden.
 */
class SupplierDueController extends Controller
{
    public function __construct(
        private readonly SupplierBalanceService $balances,
    ) {}

    public function __invoke(Request $request): \Inertia\Response
    {
        $only = (string) $request->query('only', '');

        $suppliers = Supplier::query()->orderBy('name')->get();
        $dues = $this->balances->duesForSuppliers($suppliers->pluck('id')->all());

        $filtered = $suppliers
            ->filter(function (Supplier $s) use ($dues, $only): bool {
                $due = $dues[$s->id] ?? 0.0;

                return match ($only) {
                    'due' => $due > 0,
                    'credit' => $due < 0,
                    'settled' => abs($due) < 0.005,
                    default => true,
                };
            })
            ->values()
            ->map(fn (Supplier $s): array => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
                'opening' => (float) $s->opening_balance,
                'due' => $dues[$s->id] ?? 0.0,
            ])
            ->all();

        return \Inertia\Inertia::render('Suppliers/Dues', [
            'title' => 'Supplier Due',
            'suppliers' => $filtered,
            'only' => $only,
            'summary' => [
                'totalPayable' => $this->balances->totalPayable(),
                'totalPurchases' => $this->balances->totalPurchases(),
                'totalPaid' => $this->balances->totalPaid(),
            ],
        ]);
    }

    /** Export the dues list as CSV. */
    public function export(): StreamedResponse
    {
        $suppliers = Supplier::query()->orderBy('name')->get();
        $dues = $this->balances->duesForSuppliers($suppliers->pluck('id')->all());

        $rows = $suppliers->map(fn(Supplier $s): array => [
            $s->name,
            $s->phone ?? '',
            number_format((float) $s->opening_balance, 2, '.', ''),
            number_format($dues[$s->id] ?? 0, 2, '.', ''),
            ($dues[$s->id] ?? 0) > 0 ? 'Payable' : (($dues[$s->id] ?? 0) < 0 ? 'Credit' : 'Settled'),
        ]);

        return CsvExporter::download('supplier-dues', [
            'Supplier',
            'Phone',
            'Opening Balance',
            'Current Due',
            'State',
        ], $rows);
    }
}
