<?php

namespace App\Support;

/**
 * A dashboard metric value.
 *
 * The dashboard must never display invented numbers. Until a module's real query
 * exists, a metric is reported as "unavailable" so the UI renders a neutral
 * placeholder instead of a fake figure.
 *
 * See docs/MODULES.md (Dashboard) and docs/BUSINESS_LOGIC.md.
 */
final class Metric
{
    private function __construct(
        public readonly string $label,
        public readonly string|int|float|null $value,
        public readonly string $unit = '',
        public readonly bool $available = true,
        public readonly ?string $hint = null,
        public readonly ?string $trend = null,
    ) {}

    /** A real, computed metric. */
    public static function value(
        string $label,
        string|int|float $value,
        string $unit = '',
        ?string $hint = null,
        ?string $trend = null,
    ): self {
        return new self($label, $value, $unit, true, $hint, $trend);
    }

    /**
     * A metric whose data source does not exist yet.
     * The dashboard renders "—" with a clear "pending module" note.
     */
    public static function pending(string $label, string $note = 'Awaiting module implementation'): self
    {
        return new self($label, null, '', false, $note);
    }

    /**
     * Sidebar icon name for this metric, derived from its label.
     * Keeps icon choice out of the Blade template.
     */
    public function icon(): string
    {
        return match ($this->label) {
            "Today's Sales" => 'cart',
            "Today's Feed" => 'feed',
            "Today's Income" => 'report',
            "Today's Collection" => 'book',
            'Total Due' => 'users',
            'Cash Position' => 'report',
            'Average FCR' => 'chart',
            "Today's Inspection" => 'droplet',
            'Inspection Due' => 'chart',
            'Best Pond' => 'droplet',
            default => 'grid',
        };
    }

    /** Display-ready value: real figure, or an em dash when unavailable. */
    public function display(): string
    {
        if (! $this->available || $this->value === null) {
            return '—';
        }

        return $this->unit === ''
            ? (string) $this->value
            : rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.') . ' ' . $this->unit;
    }
}
