<?php

namespace App\Services\Finance;

/**
 * Ledger / balance rules shared by customers, suppliers and parties.
 *
 * FINANCIAL LOGIC (see docs/BUSINESS_LOGIC.md — authoritative):
 *
 *   Customer:  Sale        - Payment  = Due
 *   Supplier:  Purchase    - Payment  = Due
 *   Party:     Debit       - Credit   = Balance
 *   Profit:    Income      - Expense  = Net Profit
 *
 * Sign convention (MUST be identical everywhere):
 *   positive balance → the party/farm OWES money  (a due)
 *   negative balance → credit in favour of the other side
 *   zero             → settled
 *
 * These helpers exist so the sign convention is defined once. Query-level
 * balance services (CustomerBalanceService, SupplierBalanceService,
 * PondLedgerService …) build on them during the module phases.
 */
final class LedgerRules
{
    public const ENTRY_DEBIT = 'debit';
    public const ENTRY_CREDIT = 'credit';

    /**
     * Outstanding due from a set of debits and credits.
     *
     * @param  float  $totalDebit   Amount owed to us (e.g. sales to a customer).
     * @param  float  $totalCredit  Amount settled (e.g. payments received).
     */
    public function balance(float $totalDebit, float $totalCredit): float
    {
        return round($totalDebit - $totalCredit, 2);
    }

    /** True when nothing is outstanding. */
    public function isSettled(float $balance): bool
    {
        return abs($balance) < 0.005;
    }

    /** The outstanding amount as a positive figure (for "Due" columns). */
    public function dueAmount(float $balance): float
    {
        return $balance > 0 ? $balance : 0.0;
    }

    /**
     * Net profit from income and expense totals.
     * Negative results are legitimate (a loss) and must be shown as such.
     */
    public function netProfit(float $incomeTotal, float $expenseTotal): float
    {
        return round($incomeTotal - $expenseTotal, 2);
    }

    /**
     * Safe percentage for reports (e.g. margin, achievement vs target).
     * Returns null when the base is zero rather than dividing by zero.
     */
    public function percentage(float $numerator, float $base): ?float
    {
        if ($base == 0.0) {
            return null;
        }

        return round(($numerator / $base) * 100, 2);
    }
}
