<?php

namespace App\Http\Requests\Party;

use App\Models\Party;
use App\Models\PartyTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a party transaction (debit/credit).
 *
 * SECURITY: `permission:party.transaction.create` on the route plus the check.
 * The sign convention is documented in docs/BUSINESS_LOGIC.md §3.
 */
class StorePartyTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('party.transaction.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'party_id' => ['required', 'integer', Rule::exists(Party::class, 'id')],
            'entry_type' => [
                'required',
                'string',
                Rule::in([PartyTransaction::TYPE_DEBIT, PartyTransaction::TYPE_CREDIT]),
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999.99'],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'party_id.required' => 'Please select a party.',
            'party_id.exists' => 'The selected party does not exist.',
            'entry_type.required' => 'Please choose debit or credit.',
            'entry_type.in' => 'The selected entry type is not valid.',
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
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }
}
