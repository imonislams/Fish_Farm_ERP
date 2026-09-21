<?php

namespace App\Services\Fcr;

/**
 * Feed Conversion Ratio (FCR) calculation.
 *
 * FORMULA (see docs/BUSINESS_LOGIC.md — authoritative):
 *
 *     FCR = Total Feed Consumed / Weight Gain
 *
 *     Weight Gain = (Current Avg Weight - Starting Avg Weight) * Stocked Fish Count
 *
 * Edge cases handled explicitly (never divide by zero, never show a fake value):
 *   - No feed consumed            → FCR returns null, state "no_feed"
 *   - Zero weight gain            → FCR returns null, state "no_growth"
 *   - Negative weight gain (loss) → FCR returns null, state "negative_growth"
 *   - No fish stocked             → FCR returns null, state "no_stock"
 *
 * A null FCR must be rendered as "—" with the state's explanation, never as 0.
 */
final class FcrCalculator
{
    public const STATE_OK = 'ok';
    public const STATE_NO_FEED = 'no_feed';
    public const STATE_NO_GROWTH = 'no_growth';
    public const STATE_NEGATIVE_GROWTH = 'negative_growth';
    public const STATE_NO_STOCK = 'no_stock';

    /**
     * Calculate FCR.
     *
     * @param  float  $totalFeedKg         Total feed consumed, in kg.
     * @param  float  $startingAvgWeightG  Average fish weight at stocking, in grams.
     * @param  float  $currentAvgWeightG   Current average fish weight, in grams.
     * @param  int  $stockedCount          Number of fish stocked (alive stock basis).
     */
    public function calculate(
        float $totalFeedKg,
        float $startingAvgWeightG,
        float $currentAvgWeightG,
        int $stockedCount,
    ): FcrResult {
        if ($stockedCount <= 0) {
            return FcrResult::unavailable(self::STATE_NO_STOCK, $totalFeedKg, 0.0);
        }

        // Weight gain in grams, then converted to kg to match the feed unit.
        $weightGainG = ($currentAvgWeightG - $startingAvgWeightG) * $stockedCount;
        $weightGainKg = $weightGainG / 1000;

        if ($weightGainKg < 0) {
            return FcrResult::unavailable(self::STATE_NEGATIVE_GROWTH, $totalFeedKg, $weightGainKg);
        }

        if ($weightGainKg == 0.0) {
            return FcrResult::unavailable(self::STATE_NO_GROWTH, $totalFeedKg, 0.0);
        }

        if ($totalFeedKg <= 0) {
            return FcrResult::unavailable(self::STATE_NO_FEED, 0.0, $weightGainKg);
        }

        return FcrResult::available(
            fcr: round($totalFeedKg / $weightGainKg, 4),
            totalFeedKg: $totalFeedKg,
            weightGainKg: $weightGainKg,
        );
    }
}
