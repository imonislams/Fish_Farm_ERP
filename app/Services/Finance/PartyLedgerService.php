<?php

namespace App\Services\Finance;

use App\Models\Party;
use App\Models\PartyTransaction;
use Illuminate\Support\Facades\DB;

/**
 * PartyLedgerService — generic party balances.
 *
 * EQUATION (docs/BUSINESS_LOGIC.md §3 — authoritative):
 *
 *   Party Balance = opening_balance + Σ debits − Σ credits
 *
 * Positive = the party owes the farm; negative = a credit in their favour.
 * The arithmetic comes from `LedgerRules` — the convention is defined once.
 */
class PartyLedgerService
{
    public function __construct(
        private readonly LedgerRules $rules,
    ) {}

    /** Balance for one party. */
    public function balance(Party $party): float
    {
        return $this->balancesForParties([$party->getKey()])[$party->getKey()] ?? 0.0;
    }

    /**
     * Batch balances in a fixed number of queries.
     *
     * @param  array<int, int|string>  $partyIds
     * @return array<int, float>
     */
    public function balancesForParties(array $partyIds): array
    {
        if ($partyIds === []) {
            return [];
        }

        $opening = Party::query()->whereIn('id', $partyIds)->pluck('opening_balance', 'id');

        $debits = PartyTransaction::query()
            ->whereIn('party_id', $partyIds)
            ->where('entry_type', PartyTransaction::TYPE_DEBIT)
            ->selectRaw('party_id, SUM(amount) as aggregate')
            ->groupBy('party_id')
            ->pluck('aggregate', 'party_id');

        $credits = PartyTransaction::query()
            ->whereIn('party_id', $partyIds)
            ->where('entry_type', PartyTransaction::TYPE_CREDIT)
            ->selectRaw('party_id, SUM(amount) as aggregate')
            ->groupBy('party_id')
            ->pluck('aggregate', 'party_id');

        $result = [];

        foreach ($partyIds as $id) {
            $result[$id] = $this->rules->balance(
                (float) $opening->get($id, 0) + (float) $debits->get($id, 0),
                (float) $credits->get($id, 0),
            );
        }

        return $result;
    }

    /**
     * Record a party transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): PartyTransaction
    {
        return DB::transaction(function () use ($data): PartyTransaction {
            $txn = new PartyTransaction;
            $txn->fill([
                'party_id' => $data['party_id'],
                'entry_type' => $data['entry_type'],
                'amount' => $data['amount'],
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $txn->save();

            return $txn;
        });
    }

    public function delete(PartyTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $transaction->delete();
        });
    }

    /** Guard: a party with any transaction must not be deleted. */
    public function guardDeletable(Party $party): void
    {
        $count = $party->transactions()->count();

        if ($count > 0) {
            throw new \DomainException(
                "\"{$party->name}\" has {$count} transaction(s) and cannot be deleted. "
                    . 'Mark the party inactive instead.'
            );
        }
    }
}
