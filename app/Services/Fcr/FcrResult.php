<?php

namespace App\Services\Fcr;

/**
 * Immutable FCR calculation result.
 *
 * Carries the inputs alongside the ratio so reports can explain the number,
 * and a explicit state so the UI can say "why" instead of showing a bare zero.
 */
final class FcrResult
{
    private function __construct(
        public readonly ?float $fcr,
        public readonly string $state,
        public readonly float $totalFeedKg,
        public readonly float $weightGainKg,
    ) {}

    public static function available(float $fcr, float $totalFeedKg, float $weightGainKg): self
    {
        return new self($fcr, FcrCalculator::STATE_OK, round($totalFeedKg, 3), round($weightGainKg, 3));
    }

    public static function unavailable(string $state, float $totalFeedKg, float $weightGainKg): self
    {
        return new self(null, $state, round($totalFeedKg, 3), round($weightGainKg, 3));
    }

    public function isAvailable(): bool
    {
        return $this->fcr !== null;
    }

    /** Display-ready FCR ("—" when it cannot be computed). */
    public function display(int $decimals = 2): string
    {
        return $this->fcr === null ? '—' : number_format($this->fcr, $decimals);
    }

    /** Human explanation of the result state — safe to show in the UI. */
    public function reason(): string
    {
        return match ($this->state) {
            FcrCalculator::STATE_OK => 'Calculated from feed consumed and weight gain.',
            FcrCalculator::STATE_NO_FEED => 'No feed consumption recorded for this period.',
            FcrCalculator::STATE_NO_GROWTH => 'No weight gain recorded — FCR is undefined.',
            FcrCalculator::STATE_NEGATIVE_GROWTH => 'Weight decreased — FCR is not meaningful.',
            FcrCalculator::STATE_NO_STOCK => 'No fish stocked — FCR cannot be calculated.',
            default => 'FCR unavailable.',
        };
    }

    /** Interpretation band, for reporting colour/tone only (not business logic). */
    public function band(): string
    {
        return self::bandFor($this->fcr);
    }

    /**
     * The interpretation band for a bare ratio.
     *
     * Defined ONCE here so a farm-average figure (which has no FcrResult of its
     * own) is banded by exactly the same rule as a single pond's result.
     */
    public static function bandFor(?float $fcr): string
    {
        if ($fcr === null) {
            return 'unknown';
        }

        return match (true) {
            $fcr <= 1.2 => 'excellent',
            $fcr <= 1.6 => 'good',
            $fcr <= 2.0 => 'fair',
            default => 'poor',
        };
    }
}
