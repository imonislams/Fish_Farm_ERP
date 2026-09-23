<?php

namespace App\Http\Requests\Pond;

use App\Models\FishSpecies;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a stocking from within the Pond Ledger module (New Stock).
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:pond_ledger.stocking.create` on the route AND
 *     authorize() here.
 *   - `pond_id` / `fish_species_id` validated with Rule::exists — never trusted.
 *   - `total_weight_kg` is NOT accepted from input: FishStockService derives it.
 *
 * This writes the SAME `fish_stockings` table as the Fish Stock module — the two
 * entry points share ONE stock definition and ONE write path, so the pond ledger
 * and the stock dashboard can never disagree (docs/BUSINESS_LOGIC.md §2).
 */
class StoreStockingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pond_ledger.stocking.create') ?? false;
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

            'stocked_on' => ['required', 'date', 'before_or_equal:today'],
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
            'stocked_on.required' => 'A stocking date is required.',
            'stocked_on.before_or_equal' => 'The stocking date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }
}
