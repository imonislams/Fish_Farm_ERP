<?php

namespace App\Http\Requests\Fish;

use App\Models\FishSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a fish species.
 *
 * The target comes from ROUTE MODEL BINDING, so the id in the form cannot be
 * swapped. Unique validation ignores the record being edited.
 */
class UpdateFishSpeciesRequest extends FormRequest
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
        /** @var FishSpecies|null $target */
        // Route parameter is `{species}` (routes/web.php: fish.species.*).
        $target = $this->route('species');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('fish_species', 'name')->ignore($target?->id),
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
