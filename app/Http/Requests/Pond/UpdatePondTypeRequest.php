<?php

namespace App\Http\Requests\Pond;

use App\Models\PondType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a pond type.
 *
 * The target comes from ROUTE MODEL BINDING, so the id in the form cannot be
 * swapped. Unique validation ignores the record being edited.
 */
class UpdatePondTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pond_type.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var PondType|null $target */
        // The route parameter is `{pondType}` (routes/web.php: ponds.types.*), so
        // the bound route key is `pondType` — not the snake_case `pond_type`.
        $target = $this->route('pondType');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pond_types', 'name')->ignore($target?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A pond type name is required.',
            'name.unique' => 'A pond type with this name already exists.',
            'description.max' => 'The description may not be longer than 500 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'description' => $this->filled('description')
                ? trim((string) $this->input('description'))
                : null,
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
                $validator->errors()->add('name', 'A pond type name is required.');
            }
        });
    }
}
