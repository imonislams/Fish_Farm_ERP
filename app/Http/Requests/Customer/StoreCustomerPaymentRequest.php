<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a customer payment (reduces the customer's due).
 *
 * SECURITY: `permission:customer.payment.create` on the route plus the check here.
 * Customer and the optional sale are validated with Rule::exists.
 */
class StoreCustomerPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('customer.payment.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists(Customer::class, 'id')],
            'sale_id' => ['nullable', 'integer', Rule::exists(Sale::class, 'id')],
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
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'sale_id.exists' => 'The selected invoice does not exist.',
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
