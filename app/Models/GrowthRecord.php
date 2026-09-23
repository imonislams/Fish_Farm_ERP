<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GrowthRecord — a sampled average fish weight for a pond on a given date.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * These samples are the "current weight" input to FCR (docs/BUSINESS_LOGIC.md §1).
 * The latest sample for a pond is the one FCR uses; if none exists, weight gain
 * cannot be established and FCR must render as "—", never a guess.
 */
class GrowthRecord extends Model
{
    protected $fillable = [
        'pond_id',
        'sampled_on',
        'avg_weight_g',
        'sample_size',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'avg_weight_g' => 'decimal:2',
            'sample_size' => 'integer',
            'sampled_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /** The user who recorded the sample. */
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

    /* ---------------------------------------------------------------------
     | Presentation helpers
    |---------------------------------------------------------------------*/

    /** Display-ready average weight, e.g. "250.5 g". */
    public function weightDisplay(): string
    {
        if ($this->avg_weight_g === null || $this->avg_weight_g === '') {
            return '—';
        }

        $number = rtrim(rtrim(number_format((float) $this->avg_weight_g, 2, '.', ''), '0'), '.');

        return "{$number} g";
    }

    /** The same weight in kilograms, e.g. "0.251 kg". */
    public function weightKgDisplay(): string
    {
        if ($this->avg_weight_g === null || $this->avg_weight_g === '') {
            return '—';
        }

        return number_format((float) $this->avg_weight_g / 1000, 3) . ' kg';
    }
}
