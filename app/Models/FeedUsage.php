<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FeedUsage — feed given to a pond (feed stock OUT).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A usage decreases feed stock and can never take it below zero — the service
 * rejects it before writing (docs/BUSINESS_LOGIC.md §2). It also drives FCR.
 */
class FeedUsage extends Model
{
    protected $fillable = [
        'pond_id',
        'feed_type_id',
        'quantity_kg',
        'used_on',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:3',
            'used_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function feedType(): BelongsTo
    {
        return $this->belongsTo(FeedType::class);
    }

    /** The user who recorded the usage. */
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

    /** Display-ready quantity, e.g. "12.5 kg". */
    public function quantityDisplay(): string
    {
        if ($this->quantity_kg === null || $this->quantity_kg === '') {
            return '—';
        }

        $number = rtrim(rtrim(number_format((float) $this->quantity_kg, 3, '.', ''), '0'), '.');

        return "{$number} kg";
    }
}
