<?php

namespace App\Http\Requests\Fish;

use App\Models\FishSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a fish species.
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization is enforced by `permission:fish.species.manage` on the route
 *     AND re-checked in authorize() here.
 *   - `is_active` is normalised to a boolean; there is no derived field a client
 *     could try to inject.
 */
class StoreFishSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('fish.species.manage') ?? false;
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
                // Species names are unique — two "Rohu" entries would split its
                // stock history across two records.
                Rule::unique('fish_species', 'name'),
            ],
            'local_name' => ['nullable', 'string', 'max:100'],
            'scientific_name' => ['nullable', 'string', 'max:150'],
            'default_price_per_kg' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:99999.99'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A species name is required.',
            'name.unique' => 'A species with this name already exists.',
            'name.max' => 'The species name may not be longer than 100 characters.',
            'default_price_per_kg.numeric' => 'The default price must be a number.',
            'default_price_per_kg.gte' => 'The default price cannot be negative.',
            'default_price_per_kg.decimal' => 'The default price may have at most 2 decimal places.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'local_name' => $this->filled('local_name') ? trim((string) $this->input('local_name')) : null,
            'scientific_name' => $this->filled('scientific_name') ? trim((string) $this->input('scientific_name')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'default_price_per_kg' => $this->filled('default_price_per_kg') ? $this->input('default_price_per_kg') : null,
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
                $validator->errors()->add('name', 'A species name is required.');
            }
        });
    }
}
