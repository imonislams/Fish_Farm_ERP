<?php

namespace App\Http\Requests\Finance;

use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record miscellaneous income (not a fish sale).
 *
 * SECURITY: `permission:income.create` on the route plus the check here. When a
 * pond is chosen, FinanceService also credits that pond's ledger.
 */
class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('income.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['nullable', 'integer', Rule::exists(Pond::class, 'id')],
            'category' => ['required', 'string', Rule::in(array_keys(config('finance.income_categories', [])))],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999.99'],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Please choose an income category.',
            'category.in' => 'The selected category is not valid.',
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
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'pond_id' => $this->filled('pond_id') ? $this->input('pond_id') : null,
        ]);
    }
}
