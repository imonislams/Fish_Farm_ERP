<?php

namespace App\Services\Finance;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;

/**
 * SupplierBalanceService — supplier dues.
 *
 * EQUATION (docs/BUSINESS_LOGIC.md §3 — authoritative):
 *
 *   Supplier Due = opening_balance + Σ purchases.total − Σ payments.amount
 *
 * Delegates the arithmetic to `LedgerRules` so the sign convention has one home.
 * A negative result is a credit in the farm's favour and is returned as such.
 */
class SupplierBalanceService
{
    public function __construct(
        private readonly LedgerRules $rules,
    ) {}

    /** Outstanding due for one supplier. Positive = the farm owes them. */
    public function due(Supplier $supplier): float
    {
        return $this->duesForSuppliers([$supplier->getKey()])[$supplier->getKey()] ?? 0.0;
    }

    /**
     * Batch dues for several suppliers in a fixed number of queries.
     *
     * @param  array<int, int|string>  $supplierIds
     * @return array<int, float>
     */
    public function duesForSuppliers(array $supplierIds): array
    {
        if ($supplierIds === []) {
            return [];
        }

        $opening = Supplier::query()
            ->whereIn('id', $supplierIds)
            ->pluck('opening_balance', 'id');

        $bought = Purchase::query()
            ->whereIn('supplier_id', $supplierIds)
            ->selectRaw('supplier_id, SUM(total) as aggregate')
            ->groupBy('supplier_id')
            ->pluck('aggregate', 'supplier_id');

        $paid = SupplierPayment::query()
            ->whereIn('supplier_id', $supplierIds)
            ->selectRaw('supplier_id, SUM(amount) as aggregate')
            ->groupBy('supplier_id')
            ->pluck('aggregate', 'supplier_id');

        $result = [];

        foreach ($supplierIds as $id) {
            $result[$id] = $this->rules->balance(
                (float) $opening->get($id, 0) + (float) $bought->get($id, 0),
                (float) $paid->get($id, 0),
            );
        }

        return $result;
    }

    /** Farm-wide payable: total the farm still owes suppliers. */
    public function totalPayable(): float
    {
        $opening = (float) Supplier::query()->sum('opening_balance');
        $bought = (float) Purchase::query()->sum('total');
        $paid = (float) SupplierPayment::query()->sum('amount');

        return max(0, $this->rules->balance($opening + $bought, $paid));
    }

    /** Total purchases recorded (all time). */
    public function totalPurchases(): float
    {
        return round((float) Purchase::query()->sum('total'), 2);
    }

    /** Total paid to suppliers (all time). */
    public function totalPaid(): float
    {
        return round((float) SupplierPayment::query()->sum('amount'), 2);
    }

    /** Recalculate a purchase's paid/due/status from its payments. */
    public function syncPurchase(Purchase $purchase): void
    {
        $paid = (float) $purchase->payments()->sum('amount');
        $total = (float) $purchase->total;

        $purchase->forceFill([
            'paid_amount' => $paid,
            'due_amount' => max(0, round($total - $paid, 2)),
            'status' => $this->statusFor($total, $paid),
        ])->save();
    }

    public function statusFor(float $total, float $paid): string
    {
        if ($paid <= 0.0) {
            return Purchase::STATUS_DUE;
        }

        return $paid + 0.005 >= $total ? Purchase::STATUS_PAID : Purchase::STATUS_PARTIAL;
    }

    /** Guard: a supplier with purchases or payments must not be deleted. */
    public function guardDeletable(Supplier $supplier): void
    {
        $purchases = $supplier->purchases()->count();
        $payments = $supplier->payments()->count();

        if ($purchases + $payments > 0) {
            throw new \DomainException(
                "\"{$supplier->name}\" has {$purchases} purchase(s) and {$payments} payment(s) "
                    . 'and cannot be deleted. Mark the supplier inactive instead.'
            );
        }
    }
}
