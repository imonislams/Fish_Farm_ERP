<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create or update a customer.
 *
 * One request class for both, because a customer's fields are identical on create
 * and update — only the uniqueness/authorization context differs, which is
 * handled from the route.
 *
 * SECURITY: authorization is enforced by `permission:customer.*` on the route AND
 * re-checked here. `is_active` is normalised to a boolean.
 */
class SaveCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('customer') ? 'customer.update' : 'customer.create';

        return $this->user()?->hasPermission($permission) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Customer|null $target */
        $target = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            // Email may repeat; only validate the format when one is given.
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric', 'decimal:0,2', 'min:-99999.99', 'max:99999.99'],
            'credit_limit' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:99999.99'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A customer name is required.',
            'email.email' => 'Please enter a valid email address.',
            'opening_balance.numeric' => 'The opening balance must be a number.',
            'credit_limit.gte' => 'The credit limit cannot be negative.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'opening_balance' => $this->filled('opening_balance') ? $this->input('opening_balance') : 0,
            'credit_limit' => $this->filled('credit_limit') ? $this->input('credit_limit') : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** A trimmed name must not be empty (an empty string passes `required`). */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->has('name')) {
                return;
            }

            if (trim((string) $this->input('name', '')) === '') {
                $validator->errors()->add('name', 'A customer name is required.');
            }
        });
    }
}
