<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PartyTransaction — a debit/credit entry against a party.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * SIGN CONVENTION (docs/BUSINESS_LOGIC.md §3):
 *   debit  = the party owes the farm more
 *   credit = the party has settled / the farm owes them
 *   Balance = Σ debits − Σ credits.
 */
class PartyTransaction extends Model
{
    public const TYPE_DEBIT = 'debit';

    public const TYPE_CREDIT = 'credit';

    protected $fillable = [
        'party_id',
        'entry_type',
        'amount',
        'entry_date',
        'reference',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDebit(): bool
    {
        return $this->entry_type === self::TYPE_DEBIT;
    }

    public function typeLabel(): string
    {
        return $this->isDebit() ? 'Debit' : 'Credit';
    }

    public function typeTone(): string
    {
        return $this->isDebit() ? 'danger' : 'success';
    }

    /** Signed contribution to the party balance. */
    public function signedAmount(): float
    {
        return $this->isDebit() ? (float) $this->amount : -1 * (float) $this->amount;
    }
}
