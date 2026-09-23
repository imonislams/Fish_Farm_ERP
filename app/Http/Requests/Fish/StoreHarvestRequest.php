<?php

namespace App\Http\Requests\Fish;

use App\Models\FishSpecies;
use App\Models\Pond;
use App\Services\Fish\FishStockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a harvest (stock OUT).
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:fish.harvest` on the route AND authorize() here.
 *   - `pond_id` / `fish_species_id` validated with Rule::exists — never trusted.
 *   - `total_weight_kg` is accepted (it is a measured figure) but
 *     `avg_weight_g` is DERIVED by the service from the weight and count.
 *
 * STOCK GUARD (docs/BUSINESS_LOGIC.md §2):
 *   A harvest exceeding the pond's live stock is rejected here for a clean
 *   message, and AGAIN in FishStockService (the write path).
 */
class StoreHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('fish.harvest') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'fish_species_id' => ['required', 'integer', Rule::exists(FishSpecies::class, 'id')],
            'quantity' => ['required', 'integer', 'gt:0', 'max:100000'],
            'total_weight_kg' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3', 'max:999.999'],
            'harvested_on' => ['required', 'date', 'before_or_equal:today'],
            'destination' => ['nullable', 'string', 'max:150'],
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
            'total_weight_kg.gt' => 'The total weight must be greater than zero.',
            'total_weight_kg.decimal' => 'The total weight may have at most 3 decimal places.',
            'harvested_on.required' => 'A harvest date is required.',
            'harvested_on.before_or_equal' => 'The harvest date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'destination' => $this->filled('destination') ? trim((string) $this->input('destination')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'total_weight_kg' => $this->filled('total_weight_kg') ? $this->input('total_weight_kg') : null,
        ]);
    }

    /** Reject a harvest that would take the pond's stock below zero. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->has('pond_id') || $validator->errors()->has('quantity')) {
                return;
            }

            $pond = Pond::find($this->input('pond_id'));

            if ($pond === null) {
                return;
            }

            $quantity = (int) $this->input('quantity');
            $available = app(FishStockService::class)->currentStock($pond);

            if ($quantity > $available) {
                $validator->errors()->add(
                    'quantity',
                    "Pond \"{$pond->name}\" holds only {$available} fish, so {$quantity} cannot be harvested."
                );
            }
        });
    }
}
