<?php
namespace App\Services\Feed;

/**
 * Feed stock movement rules.
 *
 * STOCK LOGIC (see docs/BUSINESS_LOGIC.md — authoritative):
 *
 *   Feed Purchase   → feed stock INCREASE
 *   Feed Usage      → feed stock DECREASE
 *   Stock Adjustment→ feed stock INCREASE or DECREASE
 *
 * Invariants enforced by this service once implemented:
 *   1. Stock is never changed silently — every change records its source record.
 *   2. Stock can never go negative (rejected with a domain exception).
 *   3. Multi-record operations run inside DB::transaction().
 *   4. Low-stock thresholds feed the notification architecture.
 */
final class FeedStockService
{
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /** @var array<string, string> */
    public const MOVEMENT_DIRECTIONS = [
        'purchase' => self::DIRECTION_IN,
        'usage' => self::DIRECTION_OUT,
        'adjustment_in' => self::DIRECTION_IN,
        'adjustment_out' => self::DIRECTION_OUT,
        'transfer_in' => self::DIRECTION_IN,
        'transfer_out' => self::DIRECTION_OUT,
        'wastage' => self::DIRECTION_OUT,
    ];

    public function wouldGoNegative(float $currentStockKg, float $quantityKg): bool
    {
        return $quantityKg > $currentStockKg;
    }

    /**
     * Apply a signed delta to a feed stock figure.
     * Feed is measured in kilograms and kept to 3 decimal places.
     */
    public function applyDelta(float $currentStockKg, string $movementType, float $quantityKg): float
    {
        $direction = self::MOVEMENT_DIRECTIONS[$movementType] ?? null;

        if ($direction === null) {
            throw new \InvalidArgumentException("Unknown feed stock movement type: {$movementType}");
        }

        if ($quantityKg < 0) {
            throw new \InvalidArgumentException('Quantity must be a positive number.');
        }

        $result = $direction === self::DIRECTION_IN
            ? $currentStockKg + $quantityKg
            : $currentStockKg - $quantityKg;

        if ($result < 0) {
            throw new \DomainException('Feed stock cannot go negative.');
        }

        return round($result, 3);
    }

    /** True when stock has reached or fallen below the configured low-stock level. */
    public function isLow(float $currentStockKg, ?float $lowStockLevelKg): bool
    {
        return $lowStockLevelKg !== null && $currentStockKg <= $lowStockLevelKg;
    }
}
