<?php

namespace App\Http\Requests\Feed;

use App\Models\FeedType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a feed type.
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization is enforced by `permission:feed.type.manage` on the route AND
 *     re-checked in authorize() here.
 *   - `is_active` is normalised to a boolean; there is no derived field a client
 *     could try to inject.
 */
class StoreFeedTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('feed.type.manage') ?? false;
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
                'max:120',
                // Feed type names are unique — two "Grower Pellet" entries would
                // split their stock history across two records.
                Rule::unique('feed_types', 'name'),
            ],
            'brand' => ['nullable', 'string', 'max:120'],
            'protein_percent' => ['nullable', 'numeric', 'gte:0', 'lte:100', 'decimal:0,2'],
            'unit' => ['nullable', 'string', 'max:30'],
            'package_weight_kg' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3', 'max:99999.999'],
            'default_unit_cost' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:99999.99'],
            'low_stock_level_kg' => ['nullable', 'numeric', 'gte:0', 'decimal:0,3', 'max:99999.999'],
            'critical_stock_level_kg' => ['nullable', 'numeric', 'gte:0', 'decimal:0,3', 'max:99999.999'],
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
            'name.required' => 'A feed name is required.',
            'name.unique' => 'A feed type with this name already exists.',
            'name.max' => 'The feed name may not be longer than 120 characters.',
            'protein_percent.lte' => 'The protein percentage cannot exceed 100.',
            'protein_percent.gte' => 'The protein percentage cannot be negative.',
            'package_weight_kg.gt' => 'The package weight must be greater than zero.',
            'default_unit_cost.gte' => 'The unit cost cannot be negative.',
            'low_stock_level_kg.gte' => 'The low-stock level cannot be negative.',
            'critical_stock_level_kg.gte' => 'The critical level cannot be negative.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'brand' => $this->filled('brand') ? trim((string) $this->input('brand')) : null,
            'unit' => $this->filled('unit') ? trim((string) $this->input('unit')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'protein_percent' => $this->filled('protein_percent') ? $this->input('protein_percent') : null,
            'package_weight_kg' => $this->filled('package_weight_kg') ? $this->input('package_weight_kg') : null,
            'default_unit_cost' => $this->filled('default_unit_cost') ? $this->input('default_unit_cost') : null,
            'low_stock_level_kg' => $this->filled('low_stock_level_kg') ? $this->input('low_stock_level_kg') : null,
            'critical_stock_level_kg' => $this->filled('critical_stock_level_kg') ? $this->input('critical_stock_level_kg') : null,
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
                $validator->errors()->add('name', 'A feed name is required.');
            }
        });
    }
}
