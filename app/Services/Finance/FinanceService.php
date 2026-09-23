<?php

namespace App\Services\Finance;

use App\Models\CustomerPayment;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\IncomeEntry;
use App\Models\Sale;
use App\Services\Pond\PondLedgerService;
use Illuminate\Support\Facades\DB;

/**
 * FinanceService — income, expenses and farm-level profit.
 *
 * NET PROFIT (docs/BUSINESS_LOGIC.md §3 — authoritative):
 *
 *   Income − Expense = Net Profit
 *
 * A NEGATIVE result is a real loss and is returned/displayed as one — never
 * clamped to zero. The arithmetic is delegated to `LedgerRules::netProfit()`.
 *
 * POND ATTRIBUTION (docs/BUSINESS_LOGIC.md §4): when an income or expense names a
 * pond, this service ALSO writes a `pond_ledger_entries` row (credit for income,
 * debit for expense) with `source_type` income/expense, so per-pond profitability
 * includes it. Deleting the entry reverses the ledger row in the same transaction.
 */
class FinanceService
{
    public function __construct(
        private readonly PondLedgerService $ledger,
        private readonly LedgerRules $rules,
    ) {}

    /* ---------------------------------------------------------------------
     | Income
    |---------------------------------------------------------------------*/

    /**
     * @param  array<string, mixed>  $data
     */
    public function createIncome(array $data): IncomeEntry
    {
        return DB::transaction(function () use ($data): IncomeEntry {
            $entry = new IncomeEntry;
            $entry->fill([
                'pond_id' => $data['pond_id'] ?? null,
                'category' => $data['category'],
                'amount' => $data['amount'],
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $entry->save();

            $this->writeIncomeLedger($entry);

            return $entry;
        });
    }

    public function deleteIncome(IncomeEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $this->ledger->reverseSource('income', $entry->getKey());
            $entry->delete();
        });
    }

    /* ---------------------------------------------------------------------
     | Expense
    |---------------------------------------------------------------------*/

    /**
     * @param  array<string, mixed>  $data
     */
    public function createExpense(array $data): ExpenseEntry
    {
        return DB::transaction(function () use ($data): ExpenseEntry {
            $entry = new ExpenseEntry;
            $entry->fill([
                'pond_id' => $data['pond_id'] ?? null,
                'expense_category_id' => $data['expense_category_id'],
                'amount' => $data['amount'],
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'paid_to' => $data['paid_to'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $entry->save();

            $this->writeExpenseLedger($entry);

            return $entry;
        });
    }

    public function deleteExpense(ExpenseEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $this->ledger->reverseSource('expense', $entry->getKey());
            $entry->delete();
        });
    }

    /**
     * Delete an expense category.
     *
     * @throws \DomainException when the category is used by any expense
     */
    public function deleteCategory(ExpenseCategory $category): void
    {
        $count = $category->expenses()->count();

        if ($count > 0) {
            throw new \DomainException(
                "\"{$category->name}\" is used by {$count} expense(s) and cannot be deleted. "
                    . 'Mark it inactive instead.'
            );
        }

        $category->delete();
    }

    /* ---------------------------------------------------------------------
     | Totals
    |---------------------------------------------------------------------*/

    /**
     * Farm-wide income, expense and net profit.
     *
     * Income  = fish sales + misc income + customer collections are NOT income
     *           (a collection settles a sale already counted) — documented so the
     *           figure is understood.
     * Expense = expense entries.
     *
     * @return array{income: float, expense: float, profit: float}
     */
    public function overview(): array
    {
        $sales = (float) Sale::query()->sum('total');
        $miscIncome = (float) IncomeEntry::query()->sum('amount');
        $expense = (float) ExpenseEntry::query()->sum('amount');

        $income = round($sales + $miscIncome, 2);
        $expense = round($expense, 2);

        return [
            'income' => $income,
            'expense' => $expense,
            'sales' => round($sales, 2),
            'misc_income' => round($miscIncome, 2),
            // Delegated to LedgerRules; a negative result is a real loss.
            'profit' => $this->rules->netProfit($income, $expense),
        ];
    }

    /** Money received from customers (settlements — not income, see overview()). */
    public function totalCollected(): float
    {
        return round((float) CustomerPayment::query()->sum('amount'), 2);
    }

    /**
     * Totals by expense category: category id => amount.
     *
     * The optional [$from, $to] date range keeps the breakdown consistent with the
     * "total expense" figure on a filtered page — without it the breakdown would
     * report all-time totals while the KPI reported the period, which is a
     * mismatch (docs/PRICING-AUDIT.md). A null bound means "unfiltered on that
     * side", so an omitted range behaves exactly as before.
     *
     * @param  array{0?: string|null, 1?: string|null}  $range
     */
    public function expenseByCategory(array $range = []): array
    {
        [$from, $to] = $range + [null, null];

        return ExpenseEntry::query()
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->selectRaw('expense_category_id, SUM(amount) as aggregate')
            ->groupBy('expense_category_id')
            ->pluck('aggregate', 'expense_category_id')
            ->map(fn($v) => round((float) $v, 2))
            ->all();
    }

    /**
     * Totals by income category: category key => amount.
     * See expenseByCategory() for why the optional date range exists.
     *
     * @param  array{0?: string|null, 1?: string|null}  $range
     */
    public function incomeByCategory(array $range = []): array
    {
        [$from, $to] = $range + [null, null];

        return IncomeEntry::query()
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->selectRaw('category, SUM(amount) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn($v) => round((float) $v, 2))
            ->all();
    }

    /** The user id to attribute a write to. */
    public function actorId(?int $userId = null): ?int
    {
        return $userId ?? auth()->id();
    }

    /* ---------------------------------------------------------------------
     | Internals
    |---------------------------------------------------------------------*/

    private function writeIncomeLedger(IncomeEntry $entry): void
    {
        if ($entry->pond_id === null) {
            return;
        }

        $this->ledger->record([
            'pond_id' => $entry->pond_id,
            'entry_type' => 'credit',
            'category' => $entry->category,
            'amount' => $entry->amount,
            'entry_date' => $entry->entry_date,
            'reference' => $entry->reference,
            'source_type' => 'income',
            'source_id' => $entry->getKey(),
            'description' => 'Income — ' . $entry->categoryLabel(),
            'created_by' => $entry->created_by,
        ]);
    }

    private function writeExpenseLedger(ExpenseEntry $entry): void
    {
        if ($entry->pond_id === null) {
            return;
        }

        $this->ledger->record([
            'pond_id' => $entry->pond_id,
            'entry_type' => 'debit',
            'category' => 'other_expense',
            'amount' => $entry->amount,
            'entry_date' => $entry->entry_date,
            'reference' => $entry->reference,
            'source_type' => 'expense',
            'source_id' => $entry->getKey(),
            'description' => $entry->category?->name
                ? 'Expense — ' . $entry->category->name
                : 'Expense',
            'created_by' => $entry->created_by,
        ]);
    }
}
