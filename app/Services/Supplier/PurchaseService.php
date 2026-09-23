<?php

namespace App\Services\Supplier;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\Finance\SupplierBalanceService;
use Illuminate\Support\Facades\DB;

/**
 * PurchaseService — purchase creation and its derived figures.
 *
 * DERIVED VALUES are computed here, never taken from input:
 *   line_total = quantity × unit_cost
 *   subtotal   = Σ line_total
 *   total      = subtotal − discount
 *   due_amount = total − paid_amount
 *   status     = paid | partial | due
 *
 * Creating a purchase writes the header and its items inside ONE transaction.
 *
 * NOTE: feed bought through this module does NOT automatically increase feed
 * stock — the Feed module owns feed stock (docs/BUSINESS_LOGIC.md §2). A purchase
 * line of type `feed` is a cost record; recording feed into stock is a separate,
 * deliberate action on the Food Purchase page. Keeping the two apart means stock
 * is never changed as a side effect of an accounting entry.
 */
class PurchaseService
{
    public function __construct(
        private readonly SupplierBalanceService $balances,
    ) {}

    /**
     * @param  array<string, mixed>  $data  header fields + `items[]`
     */
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data): Purchase {
            $lines = $this->normaliseItems($data['items'] ?? []);
            $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $total = max(0, round($subtotal - $discount, 2));
            $paid = round((float) ($data['paid_amount'] ?? 0), 2);

            $purchase = new Purchase;
            $purchase->fill([
                'supplier_id' => $data['supplier_id'],
                'invoice_no' => $data['invoice_no'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'due_amount' => max(0, round($total - $paid, 2)),
                'status' => $this->balances->statusFor($total, $paid),
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $purchase->save();

            foreach ($lines as $line) {
                $item = new PurchaseItem;
                $item->fill($line + ['purchase_id' => $purchase->getKey()]);
                $item->save();
            }

            return $purchase->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data): Purchase {
            $lines = $this->normaliseItems($data['items'] ?? []);
            $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $total = max(0, round($subtotal - $discount, 2));
            $paid = round((float) ($data['paid_amount'] ?? 0), 2);

            $purchase->fill([
                'supplier_id' => $data['supplier_id'],
                'invoice_no' => $data['invoice_no'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'due_amount' => max(0, round($total - $paid, 2)),
                'status' => $this->balances->statusFor($total, $paid),
                'note' => $data['note'] ?? null,
            ])->save();

            $purchase->items()->delete();

            foreach ($lines as $line) {
                $item = new PurchaseItem;
                $item->fill($line + ['purchase_id' => $purchase->getKey()]);
                $item->save();
            }

            return $purchase->refresh();
        });
    }

    /** Delete a purchase and its items (cascade). */
    public function delete(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase): void {
            $purchase->delete();
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normaliseItems(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $type = $item['item_type'] ?? 'other';

            $lines[] = [
                'item_type' => $type,
                // Only keep the FK that matches the chosen type.
                'feed_type_id' => $type === 'feed' ? ($item['feed_type_id'] ?? null) : null,
                'fish_species_id' => $type === 'fingerlings' ? ($item['fish_species_id'] ?? null) : null,
                'description' => $item['description'] ?? null,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'line_total' => round($quantity * $unitCost, 2),
            ];
        }

        return $lines;
    }
}
