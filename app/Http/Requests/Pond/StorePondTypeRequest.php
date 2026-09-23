<?php

namespace App\Http\Requests\Pond;

use App\Models\PondType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a pond type.
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization is enforced by `permission:pond_type.create` on the route
 *     AND re-checked in authorize() here.
 *   - `is_active` is normalised to a boolean; there is no `ponds_count` or any
 *     other derived field a client could try to inject.
 */
class StorePondTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pond_type.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                // Pond type names are unique — two "Grow Out Pond" entries would
                // make every pond's classification ambiguous.
                Rule::unique('pond_types', 'name'),
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
            'name.max' => 'The pond type name may not be longer than 100 characters.',
            'description.max' => 'The description may not be longer than 500 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Trim, and treat an all-whitespace name/description as absent so the
        // `required` rule catches it rather than storing "   ".
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'description' => $this->filled('description')
                ? trim((string) $this->input('description'))
                : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * A trimmed name must not be empty (an empty string passes `required`).
     */
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
