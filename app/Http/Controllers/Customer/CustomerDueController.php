<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Finance\CustomerBalanceService;
use App\Support\CsvExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customer dues — who owes the farm, and how much.
 *
 * Every figure is derived by CustomerBalanceService from real sales and payments.
 * A customer genuinely showing 0 has no outstanding balance; a credit (negative)
 * is shown honestly rather than hidden.
 */
class CustomerDueController extends Controller
{
    public function __construct(
        private readonly CustomerBalanceService $balances,
    ) {}

    public function __invoke(Request $request): \Inertia\Response
    {
        $only = (string) $request->query('only', '');

        $customers = Customer::query()->orderBy('name')->get();
        $dues = $this->balances->duesForCustomers($customers->pluck('id')->all());

        $filtered = $customers
            ->filter(function (Customer $c) use ($dues, $only): bool {
                $due = $dues[$c->id] ?? 0.0;

                return match ($only) {
                    'due' => $due > 0,
                    'credit' => $due < 0,
                    'settled' => abs($due) < 0.005,
                    default => true,
                };
            })
            ->values()
            ->map(fn (Customer $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'opening' => (float) $c->opening_balance,
                'due' => $dues[$c->id] ?? 0.0,
            ])
            ->all();

        return \Inertia\Inertia::render('Customers/Dues', [
            'title' => 'Customer Due',
            'customers' => $filtered,
            'only' => $only,
            'summary' => [
                'totalReceivable' => $this->balances->totalReceivable(),
                'totalSales' => $this->balances->totalSales(),
                'totalCollected' => $this->balances->totalCollected(),
            ],
            'labels' => ['title' => match ($only) {
                'due' => 'Customers owing',
                'credit' => 'Customers in credit',
                'settled' => 'Settled customers',
                default => 'All customers',
            }],
        ]);
    }

    /** Export the dues list as CSV. */
    public function export(): StreamedResponse
    {
        $customers = Customer::query()->orderBy('name')->get();
        $dues = $this->balances->duesForCustomers($customers->pluck('id')->all());

        $rows = $customers->map(fn(Customer $c): array => [
            $c->name,
            $c->phone ?? '',
            number_format((float) $c->opening_balance, 2, '.', ''),
            number_format($dues[$c->id] ?? 0, 2, '.', ''),
            ($dues[$c->id] ?? 0) > 0 ? 'Due' : (($dues[$c->id] ?? 0) < 0 ? 'Credit' : 'Settled'),
        ]);

        return CsvExporter::download('customer-dues', [
            'Customer',
            'Phone',
            'Opening Balance',
            'Current Due',
            'State',
        ], $rows);
    }
}
