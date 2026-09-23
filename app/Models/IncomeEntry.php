<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IncomeEntry — income not tied to a sale (misc. farm income).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * When a pond is set, FinanceService also attributes the money to that pond's
 * `pond_ledger_entries` (credit) so per-pond profitability stays correct.
 */
class IncomeEntry extends Model
{
    protected $fillable = [
        'pond_id',
        'category',
        'amount',
        'entry_date',
        'reference',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
        ];
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /** Human label for the category. */
    public function categoryLabel(): string
    {
        return config("finance.income_categories.{$this->category}", $this->category);
    }
}
