<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PondTransfer — fish moved from one pond to another.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A transfer is ONE atomic movement with two sides:
 *   from_pond → stock DECREASE (transfer_out)
 *   to_pond   → stock INCREASE (transfer_in)
 *
 * It is stored as a single row so the two sides cannot drift apart. The write
 * (and the stock guard on both ponds) lives in FishStockService — never here.
 *
 * The Pond Ledger timeline renders a transfer as TWO entries (a `transfer_out`
 * on the source and a `transfer_in` on the destination) so the history reads
 * correctly per pond (docs/BUSINESS_LOGIC.md §2a).
 */
class PondTransfer extends Model
{
    protected $fillable = [
        'from_pond_id',
        'to_pond_id',
        'fish_species_id',
        'quantity',
        'transferred_on',
        'reference',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'transferred_on' => 'date',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
    |---------------------------------------------------------------------*/

    /** The source pond (stock leaves here). */
    public function fromPond(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'from_pond_id');
    }

    /** The destination pond (stock arrives here). */
    public function toPond(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'to_pond_id');
    }

    /** The species moved, when one was recorded. */
    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'fish_species_id');
    }

    /** The user who recorded the transfer. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---------------------------------------------------------------------
     | Scopes
    |---------------------------------------------------------------------*/

    /** Transfers involving a pond on either side (source or destination). */
    public function scopeInvolvingPond(Builder $query, int|string $pondId): Builder
    {
        return $query->where(fn(Builder $q) => $q
            ->where('from_pond_id', $pondId)
            ->orWhere('to_pond_id', $pondId));
    }
}
