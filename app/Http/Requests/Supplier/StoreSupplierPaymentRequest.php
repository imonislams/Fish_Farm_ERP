<?php

namespace App\Http\Requests\Supplier;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a supplier payment (reduces the supplier due).
 *
 * SECURITY: `permission:supplier.payment.create` on the route plus the check here.
 */
class StoreSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier.payment.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists(Supplier::class, 'id')],
            'purchase_id' => ['nullable', 'integer', Rule::exists(Purchase::class, 'id')],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999.99'],
            'method' => ['required', 'string', Rule::in(array_keys(config('finance.payment_methods', [])))],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'purchase_id.exists' => 'The selected purchase does not exist.',
            'amount.required' => 'An amount is required.',
            'amount.gt' => 'The amount must be greater than zero.',
            'method.required' => 'Please choose a payment method.',
            'method.in' => 'The selected payment method is not valid.',
            'paid_on.required' => 'A payment date is required.',
            'paid_on.before_or_equal' => 'The payment date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference' => $this->filled('reference') ? trim((string) $this->input('reference')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }
}
