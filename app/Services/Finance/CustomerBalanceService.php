<?php

namespace App\Services\Finance;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

/**
 * CustomerBalanceService — customer dues.
 *
 * EQUATION (docs/BUSINESS_LOGIC.md §3 — authoritative):
 *
 *   Customer Due = opening_balance + Σ sales.total − Σ payments.amount
 *
 * The sign convention is NOT reimplemented: `LedgerRules::balance()` is the one
 * definition (docs/BUSINESS_LOGIC.md §3). A negative result means the customer is
 * in credit (they overpaid) and is returned as such, never clamped.
 *
 * Everything is derived from the real records — the due is never stored.
 */
class CustomerBalanceService
{
    public function __construct(
        private readonly LedgerRules $rules,
    ) {}

    /**
     * Outstanding due for one customer.
     * Positive = they owe the farm; negative = credit in their favour.
     */
    public function due(Customer $customer): float
    {
        return $this->duesForCustomers([$customer->getKey()])[$customer->getKey()] ?? 0.0;
    }

    /**
     * Batch dues for several customers in a fixed number of queries.
     * Safe for a whole list page (no N+1).
     *
     * @param  array<int, int|string>  $customerIds
     * @return array<int, float>
     */
    public function duesForCustomers(array $customerIds): array
    {
        if ($customerIds === []) {
            return [];
        }

        $opening = Customer::query()
            ->whereIn('id', $customerIds)
            ->pluck('opening_balance', 'id');

        $sold = Sale::query()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, SUM(total) as aggregate')
            ->groupBy('customer_id')
            ->pluck('aggregate', 'customer_id');

        $paid = CustomerPayment::query()
            ->whereIn('customer_id', $customerIds)
            ->selectRaw('customer_id, SUM(amount) as aggregate')
            ->groupBy('customer_id')
            ->pluck('aggregate', 'customer_id');

        $result = [];

        foreach ($customerIds as $id) {
            // debits = what they owe us; credits = what they have paid.
            $result[$id] = $this->rules->balance(
                (float) $opening->get($id, 0) + (float) $sold->get($id, 0),
                (float) $paid->get($id, 0),
            );
        }

        return $result;
    }

    /**
     * Farm-wide receivable: total still owed by all customers (excluding credits).
     */
    public function totalReceivable(): float
    {
        $opening = (float) Customer::query()->sum('opening_balance');
        $sold = (float) Sale::query()->sum('total');
        $paid = (float) CustomerPayment::query()->sum('amount');

        return max(0, $this->rules->balance($opening + $sold, $paid));
    }

    /** Total sales value recorded (all time). */
    public function totalSales(): float
    {
        return round((float) Sale::query()->sum('total'), 2);
    }

    /** Total collected from customers (all time). */
    public function totalCollected(): float
    {
        return round((float) CustomerPayment::query()->sum('amount'), 2);
    }

    /**
     * Recalculate a sale's paid amount, due and status from its payments.
     *
     * Called whenever a payment is added or removed so a sale's settlement state
     * is always consistent with the money actually received.
     */
    public function syncSale(Sale $sale): void
    {
        $paid = (float) $sale->payments()->sum('amount');
        $total = (float) $sale->total;

        $sale->forceFill([
            'paid_amount' => $paid,
            'due_amount' => max(0, round($total - $paid, 2)),
            'status' => $this->statusFor($total, $paid),
        ])->save();
    }

    /** Settlement status from the total and what has been paid. */
    public function statusFor(float $total, float $paid): string
    {
        if ($paid <= 0.0) {
            return Sale::STATUS_DUE;
        }

        return $paid + 0.005 >= $total ? Sale::STATUS_PAID : Sale::STATUS_PARTIAL;
    }

    /** Guard: a customer with sales or payments must not be deleted. */
    public function guardDeletable(Customer $customer): void
    {
        $sales = $customer->sales()->count();
        $payments = $customer->payments()->count();

        if ($sales + $payments > 0) {
            throw new \DomainException(
                "\"{$customer->name}\" has {$sales} sale(s) and {$payments} payment(s) "
                    . 'and cannot be deleted. Mark the customer inactive instead.'
            );
        }
    }

    /** Run a customer write inside a transaction (multi-record safety). */
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
