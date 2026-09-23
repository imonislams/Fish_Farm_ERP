<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Party — a generic ledger counterparty (landlord, agent, worker, …).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * Balance = Σ debits − Σ credits, derived by Finance\PartyLedgerService
 * (docs/BUSINESS_LOGIC.md §3). Positive = the party owes the farm.
 */
class Party extends Model
{
    protected $fillable = [
        'name',
        'type',
        'phone',
        'address',
        'opening_balance',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PartyTransaction::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
        $like = '%' . $escaped . '%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('name', 'like', $like)->orWhere('phone', 'like', $like);
        });
    }

    /** Human label for the party type. */
    public function typeLabel(): string
    {
        return config("finance.party_types.{$this->type}.label", $this->type);
    }

    /** A party with any transaction must not be deleted. */
    public function isDeletable(): bool
    {
        // Prefer the eager-loaded count (index pages use withCount); fall back to
        // a live query when the count was not pre-loaded.
        $transactions = $this->transactions_count ?? $this->transactions()->count();

        return $transactions === 0;
    }
}
