<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Finance\CustomerBalanceService;
use App\Services\Pond\PondLedgerService;
use Illuminate\Support\Facades\DB;

/**
 * SalesService — sale creation and its effects.
 *
 * Creating a sale writes, in ONE transaction (docs/BUSINESS_LOGIC.md §6):
 *   1. the `sales` header,
 *   2. its `sale_items`,
 *   3. a pond ledger CREDIT for each line's pond, so per-pond profitability
 *      includes the revenue (docs/BUSINESS_LOGIC.md §4).
 *
 * DERIVED VALUES are computed here and never taken from input:
 *   line_total = weight_kg × unit_price  (or quantity × unit_price when sold by count)
 *   subtotal   = Σ line_total
 *   total      = subtotal − discount
 *   due_amount = total − paid_amount
 *   status     = paid | partial | due
 *
 * Deleting a sale reverses its ledger entries IN THE SAME TRANSACTION, so no
 * orphan ledger row is ever left behind.
 */
class SalesService
{
    public function __construct(
        private readonly PondLedgerService $ledger,
        private readonly CustomerBalanceService $balances,
    ) {}

    /**
     * Create a sale with its items.
     *
     * @param  array<string, mixed>  $data  header fields + `items[]`
     */
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data): Sale {
            $lines = $this->normaliseItems($data['items'] ?? []);
            $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $total = max(0, round($subtotal - $discount, 2));
            $paid = round((float) ($data['paid_amount'] ?? 0), 2);

            $sale = new Sale;
            $sale->fill([
                'customer_id' => $data['customer_id'],
                'invoice_no' => $data['invoice_no'],
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'due_amount' => max(0, round($total - $paid, 2)),
                'status' => $this->balances->statusFor($total, $paid),
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $sale->save();

            foreach ($lines as $line) {
                $item = new SaleItem;
                $item->fill($line + ['sale_id' => $sale->getKey()]);
                $item->save();
            }

            // Attribute the revenue to each pond it came from.
            $this->writeLedgerEntries($sale, $lines);

            return $sale->refresh();
        });
    }

    /**
     * Update a sale's header, replacing its items and ledger entries.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data): Sale {
            // Reverse the old ledger entries before recomputing.
            $this->ledger->reverseSource('sale', $sale->getKey());

            $lines = $this->normaliseItems($data['items'] ?? []);
            $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $total = max(0, round($subtotal - $discount, 2));
            $paid = round((float) ($data['paid_amount'] ?? 0), 2);

            $sale->fill([
                'customer_id' => $data['customer_id'],
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'due_amount' => max(0, round($total - $paid, 2)),
                'status' => $this->balances->statusFor($total, $paid),
                'note' => $data['note'] ?? null,
            ])->save();

            // Replace the lines (cascade would also clear them, but be explicit).
            $sale->items()->delete();

            foreach ($lines as $line) {
                $item = new SaleItem;
                $item->fill($line + ['sale_id' => $sale->getKey()]);
                $item->save();
            }

            $this->writeLedgerEntries($sale, $lines);

            return $sale->refresh();
        });
    }

    /**
     * Delete a sale, reversing its ledger entries in the same transaction.
     */
    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $this->ledger->reverseSource('sale', $sale->getKey());
            $sale->delete();   // sale_items cascade
        });
    }

    /* ---------------------------------------------------------------------
     | Internals
    |---------------------------------------------------------------------*/

    /**
     * Normalise raw item input into sale_items rows with computed line totals.
     *
     * A line may be sold by weight (weight_kg) or by count (quantity). Weight wins
     * when both are present, because fish is sold by weight.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normaliseItems(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $weight = $item['weight_kg'] ?? null;
            $quantity = $item['quantity'] ?? null;
            $unitPrice = (float) ($item['unit_price'] ?? 0);

            $basis = ($weight !== null && (float) $weight > 0)
                ? (float) $weight
                : (float) ($quantity ?? 0);

            $lines[] = [
                'fish_species_id' => $item['fish_species_id'] ?? null,
                'pond_id' => $item['pond_id'] ?? null,
                'quantity' => $quantity !== null && $quantity !== '' ? (int) $quantity : null,
                'weight_kg' => $weight !== null && $weight !== '' ? $weight : null,
                'unit_price' => $unitPrice,
                'line_total' => round($basis * $unitPrice, 2),
                'description' => $item['description'] ?? null,
            ];
        }

        return $lines;
    }

    /**
     * Write a pond ledger CREDIT for each sale line that names a pond.
     *
     * Only lines with a pond contribute — a farm-wide sale line has no pond to
     * attribute the money to, and is therefore not added to any pond's ledger.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function writeLedgerEntries(Sale $sale, array $lines): void
    {
        foreach ($lines as $index => $line) {
            if (empty($line['pond_id']) || (float) $line['line_total'] <= 0) {
                continue;
            }

            $this->ledger->record([
                'pond_id' => $line['pond_id'],
                'entry_type' => 'credit',
                'category' => 'fish_sale',
                'amount' => $line['line_total'],
                'entry_date' => $sale->sale_date,
                'reference' => $sale->invoice_no,
                'source_type' => 'sale',
                'source_id' => $sale->getKey(),
                'description' => 'Sale ' . $sale->invoice_no . ' (line ' . ($index + 1) . ')',
                'created_by' => $sale->created_by,
            ]);
        }
    }

    /** Generate the next invoice number, e.g. SAL-2026-0007. */
    public function nextInvoiceNo(): string
    {
        $prefix = config('finance.invoice_prefixes.sale', 'SAL');
        $year = now()->format('Y');

        $last = Sale::query()
            ->where('invoice_no', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('invoice_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $next);
    }
}
