<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PondLedgerEntry — money in/out attributed to a specific pond.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * SIGN CONVENTION (docs/BUSINESS_LOGIC.md §4 — authoritative):
 *   debit  = money OUT (a cost attributed to the pond)
 *   credit = money IN  (revenue attributed to the pond)
 *   Pond Profit = Σ credits − Σ debits
 *
 * The balance arithmetic lives once in App\Services\Finance\LedgerRules, applied
 * by App\Services\Pond\PondLedgerService. Nothing here recomputes it.
 *
 * This model is deliberately thin: entries are written by PondLedgerService (the
 * write path), never by a controller directly.
 */
class PondLedgerEntry extends Model
{
    /** Canonical type keys. Defined once — never hard-coded in controllers/views. */
    public const TYPE_DEBIT = 'debit';

    public const TYPE_CREDIT = 'credit';

    /** Source type for a hand-recorded entry with no owning module row. */
    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'pond_id',
        'entry_type',
        'category',
        'amount',
        'entry_date',
        'reference',
        'source_type',
        'source_id',
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

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /** The user who recorded the entry. */
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

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('entry_type', $type);
    }

    /** Free-text search across the fields a user searches by. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        // Escape LIKE wildcards so "%" or "_" match a literal character rather
        // than everything. Backslash first, or it would double-escape the rest.
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $like = '%' . $escaped . '%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('reference', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    /* ---------------------------------------------------------------------
     | Behaviour / presentation
    |---------------------------------------------------------------------*/

    /** True when this entry is money IN (revenue). */
    public function isCredit(): bool
    {
        return $this->entry_type === self::TYPE_CREDIT;
    }

    /** Human label for the type, e.g. "Income (credit)". */
    public function typeLabel(): string
    {
        return config("ledger.entry_types.{$this->entry_type}.label", $this->entry_type);
    }

    /** Short label for the type, e.g. "Income" (for tight table cells). */
    public function typeShortLabel(): string
    {
        return config("ledger.entry_types.{$this->entry_type}.short", $this->entry_type);
    }

    /** Badge tone for the type (success | danger | default). */
    public function typeTone(): string
    {
        return config("ledger.entry_types.{$this->entry_type}.tone", 'default');
    }

    /**
     * Human label for the category, looked up under the entry's own type.
     * Falls back to the stored key when it is not a known one.
     */
    public function categoryLabel(): string
    {
        if ($this->category === null || $this->category === '') {
            return '—';
        }

        return config("ledger.categories.{$this->entry_type}.{$this->category}", $this->category);
    }

    /** Human label for the source type, e.g. "Feed usage". */
    public function sourceTypeLabel(): string
    {
        return config("ledger.source_types.{$this->source_type}", $this->source_type);
    }

    /** Display-ready signed amount, e.g. "+1,200.00" or "−450.00". */
    public function signedAmountDisplay(): string
    {
        $amount = number_format((float) $this->amount, 2);

        return $this->isCredit() ? "+{$amount}" : "−{$amount}";
    }

    /**
     * The signed contribution of this entry to the pond's profit.
     * Positive for a credit, negative for a debit.
     */
    public function signedAmount(): float
    {
        return $this->isCredit() ? (float) $this->amount : -1 * (float) $this->amount;
    }
}
