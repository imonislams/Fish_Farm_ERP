<?php

namespace App\Services\Fish;

/**
 * Fish stock movement rules.
 *
 * STOCK LOGIC (see docs/BUSINESS_LOGIC.md — authoritative):
 *
 *   Fish Stocking  → fish stock INCREASE
 *   Mortality      → fish stock DECREASE
 *   Harvest        → fish stock DECREASE
 *
 * Invariants enforced by this service once implemented:
 *   1. Stock is never changed silently — every change records its source record.
 *   2. Stock can never go negative (rejected with a domain exception).
 *   3. Multi-record operations run inside DB::transaction().
 *
 * This class defines the contract now; persistence is wired in the Fish Stock
 * module phase. It is intentionally NOT a stub that returns fake numbers.
 */
final class FishStockService
{
    /** Direction constants — reused by ledgers and reports so signs stay consistent. */
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /**
     * Movement types and their stock direction.
     * Registering a new movement type requires adding it here first.
     *
     * @var array<string, string>
     */
    public const MOVEMENT_DIRECTIONS = [
        'stocking' => self::DIRECTION_IN,
        'mortality' => self::DIRECTION_OUT,
        'harvest' => self::DIRECTION_OUT,
        'adjustment_in' => self::DIRECTION_IN,
        'adjustment_out' => self::DIRECTION_OUT,
        'transfer_in' => self::DIRECTION_IN,
        'transfer_out' => self::DIRECTION_OUT,
    ];

    /**
     * Whether a proposed movement would drive the pond stock negative.
     * Used by validation BEFORE a write is attempted.
     *
     * @param  int  $currentStock   Fish currently alive in the pond.
     * @param  int  $quantity       Quantity to remove (positive integer).
     */
    public function wouldGoNegative(int $currentStock, int $quantity): bool
    {
        return $quantity > $currentStock;
    }

    /** Apply a signed delta to a stock figure, enforcing the non-negative rule. */
    public function applyDelta(int $currentStock, string $movementType, int $quantity): int
    {
        $direction = self::MOVEMENT_DIRECTIONS[$movementType] ?? null;

        if ($direction === null) {
            throw new \InvalidArgumentException("Unknown fish stock movement type: {$movementType}");
        }

        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be a positive integer.');
        }

        $result = $direction === self::DIRECTION_IN
            ? $currentStock + $quantity
            : $currentStock - $quantity;

        if ($result < 0) {
            throw new \DomainException('Fish stock cannot go negative.');
        }

        return $result;
    }
}
