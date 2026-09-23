<?php

namespace App\Services\Pond;

use App\Models\Pond;
use App\Models\PondLedgerEntry;
use App\Services\Finance\LedgerRules;
use Illuminate\Support\Facades\DB;

/**
 * Pond ledger business logic — THE money rules for per-pond profitability.
 *
 * BUSINESS RULES (docs/BUSINESS_LOGIC.md §4 — authoritative):
 *
 *   Pond Profit = Σ credits (income) − Σ debits (expenses)
 *
 *   1. Entries are written by the service that owns the originating transaction
 *      (a sale, a feed usage, an expense). Controllers never write ledger rows —
 *      they call this service.
 *   2. Every entry records `source_type` + `source_id` for traceability.
 *   3. Deleting a source transaction must reverse its ledger entry IN THE SAME
 *      transaction — never leave an orphan ledger row.
 *
 * The sign arithmetic is NOT reimplemented here: it delegates to
 * App\Services\Finance\LedgerRules (docs/BUSINESS_LOGIC.md §3), which is the one
 * place the convention is defined.
 *
 * A negative profit is a genuine loss and is returned as such — never clamped.
 */
class PondLedgerService
{
    public function __construct(
        private readonly LedgerRules $rules,
    ) {}

    /* ---------------------------------------------------------------------
     | Balance / profit calculations — the ONE definition
    |---------------------------------------------------------------------*/

    /**
     * A pond's profit: total credits − total debits.
     *
     * Negative results are legitimate losses and are returned unchanged.
     */
    public function pondProfit(Pond $pond): float
    {
        return $this->profitForPondIds([$pond->getKey()])[$pond->getKey()] ?? 0.0;
    }

    /**
     * Batch form: profit for several ponds in a fixed number of queries.
     * Safe to call for a whole list page (no N+1).
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, float>  pond id => profit
     */
    public function profitForPondIds(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        $credits = PondLedgerEntry::query()
            ->whereIn('pond_id', $pondIds)
            ->where('entry_type', PondLedgerEntry::TYPE_CREDIT)
            ->selectRaw('pond_id, SUM(amount) as aggregate')
            ->groupBy('pond_id')
            ->pluck('aggregate', 'pond_id');

        $debits = PondLedgerEntry::query()
            ->whereIn('pond_id', $pondIds)
            ->where('entry_type', PondLedgerEntry::TYPE_DEBIT)
            ->selectRaw('pond_id, SUM(amount) as aggregate')
            ->groupBy('pond_id')
            ->pluck('aggregate', 'pond_id');

        $result = [];

        foreach ($pondIds as $id) {
            // Delegated to LedgerRules so the convention has one home.
            $result[$id] = $this->rules->netProfit(
                (float) $credits->get($id, 0),
                (float) $debits->get($id, 0),
            );
        }

        return $result;
    }

    /**
     * Income and expense totals for a pond (used by the ledger dashboard).
     *
     * @return array{income: float, expense: float, profit: float}
     */
    public function pondSummary(Pond $pond): array
    {
        return $this->summariesForPondIds([$pond->getKey()])[$pond->getKey()]
            ?? ['income' => 0.0, 'expense' => 0.0, 'profit' => 0.0];
    }

    /**
     * Batch form: income / expense / profit for several ponds.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, array{income: float, expense: float, profit: float}>
     */
    public function summariesForPondIds(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        $credits = PondLedgerEntry::query()
            ->whereIn('pond_id', $pondIds)
            ->where('entry_type', PondLedgerEntry::TYPE_CREDIT)
            ->selectRaw('pond_id, SUM(amount) as aggregate')
            ->groupBy('pond_id')
            ->pluck('aggregate', 'pond_id');

        $debits = PondLedgerEntry::query()
            ->whereIn('pond_id', $pondIds)
            ->where('entry_type', PondLedgerEntry::TYPE_DEBIT)
            ->selectRaw('pond_id, SUM(amount) as aggregate')
            ->groupBy('pond_id')
            ->pluck('aggregate', 'pond_id');

        $result = [];

        foreach ($pondIds as $id) {
            $income = round((float) $credits->get($id, 0), 2);
            $expense = round((float) $debits->get($id, 0), 2);

            $result[$id] = [
                'income' => $income,
                'expense' => $expense,
                // Delegated to LedgerRules — the single sign convention.
                'profit' => $this->rules->netProfit($income, $expense),
            ];
        }

        return $result;
    }

    /**
     * Farm-wide totals across every pond.
     *
     * @return array{income: float, expense: float, profit: float}
     */
    public function totalSummary(): array
    {
        $income = round((float) PondLedgerEntry::query()
            ->where('entry_type', PondLedgerEntry::TYPE_CREDIT)
            ->sum('amount'), 2);

        $expense = round((float) PondLedgerEntry::query()
            ->where('entry_type', PondLedgerEntry::TYPE_DEBIT)
            ->sum('amount'), 2);

        return [
            'income' => $income,
            'expense' => $expense,
            'profit' => $this->rules->netProfit($income, $expense),
        ];
    }

    /**
     * Totals grouped by category for one type — powers the category breakdown.
     *
     * @return array<string, float>  category key => total amount
     */
    public function totalsByCategory(string $entryType): array
    {
        return PondLedgerEntry::query()
            ->where('entry_type', $entryType)
            ->selectRaw('category, SUM(amount) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category')
            ->map(fn($value) => round((float) $value, 2))
            ->all();
    }

    /** True when nothing is outstanding for this pond (profit ≈ 0). */
    public function isSettled(Pond $pond): bool
    {
        return $this->rules->isSettled($this->pondProfit($pond));
    }

    /* ---------------------------------------------------------------------
     | Write path — the ONE way an entry is created
    |---------------------------------------------------------------------*/

    /**
     * Record a ledger entry.
     *
     * This is the ONLY way a row is created. Owning services (sales, feed usage,
     * expenses — when they land) call this with their own `source_type` and
     * `source_id`; a manual entry uses source_type 'manual'.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): PondLedgerEntry
    {
        return DB::transaction(function () use ($data): PondLedgerEntry {
            $entry = new PondLedgerEntry;
            $entry->fill([
                'pond_id' => $data['pond_id'],
                'entry_type' => $data['entry_type'],
                'category' => $data['category'],
                'amount' => $data['amount'],
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'source_type' => $data['source_type'] ?? PondLedgerEntry::SOURCE_MANUAL,
                'source_id' => $data['source_id'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $entry->save();

            return $entry;
        });
    }

    /**
     * Reverse (delete) the ledger entries that belong to a source transaction.
     *
     * Called by the owning service when it deletes its own record, INSIDE the
     * same transaction, so a deleted sale/usage never leaves an orphan ledger row
     * (docs/BUSINESS_LOGIC.md §4 rule 3).
     *
     * @return int  the number of rows removed
     */
    public function reverseSource(string $sourceType, int|string $sourceId): int
    {
        return PondLedgerEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    /**
     * Delete a hand-recorded entry.
     *
     * Only entries with no owning module row (source_type 'manual') may be
     * deleted from the UI: a generated entry must be reversed by the service that
     * owns its source, never removed out from under it.
     *
     * @throws \DomainException when the entry was generated by another module
     */
    public function delete(PondLedgerEntry $entry): void
    {
        if ($entry->source_type !== PondLedgerEntry::SOURCE_MANUAL) {
            throw new \DomainException(
                "This entry was generated by \"{$entry->sourceTypeLabel()}\" and cannot be deleted here. "
                    . 'Remove the source record instead — its ledger entry is reversed automatically.'
            );
        }

        DB::transaction(function () use ($entry): void {
            $entry->delete();
        });
    }

    /** The user id to attribute a write to, or null when unauthenticated. */
    public function actorId(?int $userId = null): ?int
    {
        return $userId ?? auth()->id();
    }
}
