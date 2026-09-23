<?php

namespace App\Http\Requests\Supplier;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create or update a supplier.
 *
 * SECURITY: `permission:supplier.*` on the route plus the check here.
 */
class SaveSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->route('supplier') ? 'supplier.update' : 'supplier.create';

        return $this->user()?->hasPermission($permission) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric', 'decimal:0,2', 'min:-99999.99', 'max:99999.99'],
            'is_active' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A supplier name is required.',
            'email.email' => 'Please enter a valid email address.',
            'opening_balance.numeric' => 'The opening balance must be a number.',
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
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->has('name')) {
                return;
            }

            if (trim((string) $this->input('name', '')) === '') {
                $validator->errors()->add('name', 'A supplier name is required.');
            }
        });
    }
}
