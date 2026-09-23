<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inspection — a pond inspection and its findings.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * Measurement columns are nullable: an inspection may cover only some parameters,
 * and "not measured" must stay null (rendered "—") rather than 0, which would be a
 * lie that pollutes every later average (docs/BUSINESS_LOGIC.md §9 rule 6).
 *
 * `health_status` is always set — an inspection reaches a conclusion even when
 * that conclusion is "monitor".
 */
class Inspection extends Model
{
    protected $fillable = [
        'pond_id',
        'inspected_on',
        'inspected_by',
        'water_ph',
        'water_temp_c',
        'dissolved_oxygen',
        'ammonia',
        'turbidity',
        'health_status',
        'action_taken',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'water_ph' => 'decimal:2',
            'water_temp_c' => 'decimal:2',
            'dissolved_oxygen' => 'decimal:2',
            'ammonia' => 'decimal:3',
            'turbidity' => 'decimal:2',
            'inspected_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /** The user who recorded the inspection. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------------------------------------------------------------
     | Scopes
     |---------------------------------------------------------------------*/

    public function scopeForPond(Builder $query, int|string $pondId): Builder
    {
        return $query->where('pond_id', $pondId);
    }

    /** Only inspections that flagged a concern (warning / critical). */
    public function scopeConcerning(Builder $query): Builder
    {
        $keys = collect(config('fcr.health_statuses', []))
            ->filter(fn(array $meta) => $meta['concern'] ?? false)
            ->keys()
            ->all();

        return $query->whereIn('health_status', $keys);
    }

    /* ---------------------------------------------------------------------
     | Presentation helpers
    |---------------------------------------------------------------------*/

    /** Human label for the health status, e.g. "Warning". */
    public function healthLabel(): string
    {
        return config("fcr.health_statuses.{$this->health_status}.label", $this->health_status);
    }

    /** Badge tone for the health status. */
    public function healthTone(): string
    {
        return config("fcr.health_statuses.{$this->health_status}.tone", 'default');
    }

    /** True when this inspection flagged a concern. */
    public function isConcerning(): bool
    {
        return (bool) config("fcr.health_statuses.{$this->health_status}.concern", false);
    }

    /**
     * The recorded parameters as label => display string, skipping anything not
     * measured. Used by the details view so no arithmetic or formatting lives in
     * Blade.
     *
     * @return array<string, string>
     */
    public function parameterReadings(): array
    {
        $readings = [];

        foreach (config('fcr.parameters', []) as $key => $meta) {
            $value = $this->{$key};

            if ($value === null || $value === '') {
                continue;
            }

            $decimals = (int) ($meta['decimals'] ?? 2);
            $suffix = $meta['suffix'] ?? '';

            $readings[$meta['label']] = rtrim(rtrim(number_format((float) $value, $decimals, '.', ''), '0'), '.')
                . ($suffix === '' ? '' : " {$suffix}");
        }

        return $readings;
    }
}
