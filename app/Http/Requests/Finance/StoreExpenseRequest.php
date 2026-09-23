<?php

namespace App\Http\Requests\Finance;

use App\Models\ExpenseCategory;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record an expense.
 *
 * SECURITY: `permission:expense.create` on the route plus the check here. When a
 * pond is chosen, FinanceService also debits that pond's ledger.
 */
class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('expense.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['nullable', 'integer', Rule::exists(Pond::class, 'id')],
            'expense_category_id' => ['required', 'integer', Rule::exists(ExpenseCategory::class, 'id')],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999.99'],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'paid_to' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'expense_category_id.required' => 'Please choose a category.',
            'expense_category_id.exists' => 'The selected category does not exist.',
            'amount.required' => 'An amount is required.',
            'amount.gt' => 'The amount must be greater than zero.',
            'entry_date.required' => 'A date is required.',
            'entry_date.before_or_equal' => 'The date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference' => $this->filled('reference') ? trim((string) $this->input('reference')) : null,
            'paid_to' => $this->filled('paid_to') ? trim((string) $this->input('paid_to')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'pond_id' => $this->filled('pond_id') ? $this->input('pond_id') : null,
        ]);
    }
}
