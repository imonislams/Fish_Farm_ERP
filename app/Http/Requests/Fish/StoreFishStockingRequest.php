<?php

namespace App\Http\Requests\Fish;

use App\Models\FishSpecies;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a fish stocking (stock IN).
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:fish.stock` on the route AND authorize() here.
 *   - `pond_id` / `fish_species_id` validated with Rule::exists — never trusted.
 *   - `total_weight_kg` is NOT accepted from input: the service derives it from
 *     the count and average weight, so the stored figure is always consistent.
 *
 * A stocking INCREASES stock, so it can never be negative — only the pond having
 * a valid type is checked. (The pond may legitimately be `empty`/`active`.)
 */
class StoreFishStockingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('fish.stock') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'fish_species_id' => ['required', 'integer', Rule::exists(FishSpecies::class, 'id')],

            // A count of fish — a positive whole number.
            'quantity' => ['required', 'integer', 'gt:0', 'max:100000'],

            'avg_weight_g' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999.99'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:9999.99'],

            'stocked_on' => ['required', 'date', 'before_or_equal:today'],

            'supplier_name' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pond_id.required' => 'Please select a pond.',
            'pond_id.exists' => 'The selected pond does not exist.',
            'fish_species_id.required' => 'Please select a species.',
            'fish_species_id.exists' => 'The selected species does not exist.',
            'quantity.required' => 'A quantity is required.',
            'quantity.integer' => 'The quantity must be a whole number of fish.',
            'quantity.gt' => 'The quantity must be greater than zero.',
            'avg_weight_g.gt' => 'The average weight must be greater than zero.',
            'avg_weight_g.decimal' => 'The average weight may have at most 2 decimal places.',
            'unit_cost.gte' => 'The unit cost cannot be negative.',
            'stocked_on.required' => 'A stocking date is required.',
            'stocked_on.before_or_equal' => 'The stocking date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'supplier_name' => $this->filled('supplier_name') ? trim((string) $this->input('supplier_name')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'avg_weight_g' => $this->filled('avg_weight_g') ? $this->input('avg_weight_g') : null,
            'unit_cost' => $this->filled('unit_cost') ? $this->input('unit_cost') : null,
        ]);
    }
}
