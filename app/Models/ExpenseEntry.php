<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExpenseEntry — a recorded expense (feed, labour, medicine, electricity, …).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * When a pond is set, FinanceService also attributes the money to that pond's
 * `pond_ledger_entries` (debit) so per-pond profitability stays correct.
 */
class ExpenseEntry extends Model
{
    protected $fillable = [
        'pond_id',
        'expense_category_id',
        'amount',
        'entry_date',
        'reference',
        'paid_to',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOfCategory(Builder $query, int|string $categoryId): Builder
    {
        return $query->where('expense_category_id', $categoryId);
    }
}
