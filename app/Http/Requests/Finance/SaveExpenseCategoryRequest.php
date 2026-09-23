<?php

namespace App\Http\Requests\Finance;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or update an expense category (editable master data).
 *
 * SECURITY: `permission:expense.category.manage` on the route plus the check here.
 */
class SaveExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('expense.category.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ExpenseCategory|null $target */
        $target = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expense_categories', 'name')->ignore($target?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'A category name is required.',
            'name.unique' => 'A category with this name already exists.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
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
                $validator->errors()->add('name', 'A category name is required.');
            }
        });
    }
}
