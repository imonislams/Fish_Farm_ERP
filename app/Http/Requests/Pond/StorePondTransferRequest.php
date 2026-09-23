<?php

namespace App\Http\Requests\Pond;

use App\Models\Pond;
use App\Models\PondTransfer;
use App\Services\Fish\FishStockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a pond-to-pond transfer (stock OUT of the source, IN to the destination).
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:pond_ledger.transfer.create` on the route AND
 *     authorize() here.
 *   - `from_pond_id` / `to_pond_id` validated with Rule::exists — never trusted.
 *   - `fish_species_id` is optional and validated against `fish_species`.
 *
 * TRANSFER GUARD (docs/BUSINESS_LOGIC.md §2a):
 *   - Source and destination must differ.
 *   - Quantity must be greater than zero.
 *   - The source must hold enough live stock. This is checked here for a clean
 *     field message and AGAIN in FishStockService (the write path).
 */
class StorePondTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pond_ledger.transfer.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'to_pond_id' => ['required', 'integer', 'different:from_pond_id', Rule::exists(Pond::class, 'id')],
            'fish_species_id' => ['nullable', 'integer', Rule::exists(\App\Models\FishSpecies::class, 'id')],
            'quantity' => ['required', 'integer', 'gt:0', 'max:1000000'],
            'transferred_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from_pond_id.required' => 'Please select a source pond.',
            'from_pond_id.exists' => 'The selected source pond does not exist.',
            'to_pond_id.required' => 'Please select a destination pond.',
            'to_pond_id.different' => 'The source and destination pond must be different.',
            'to_pond_id.exists' => 'The selected destination pond does not exist.',
            'quantity.required' => 'A quantity is required.',
            'quantity.integer' => 'The quantity must be a whole number of fish.',
            'quantity.gt' => 'The quantity must be greater than zero.',
            'transferred_on.required' => 'A date is required.',
            'transferred_on.before_or_equal' => 'The date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'fish_species_id' => $this->filled('fish_species_id') ? $this->input('fish_species_id') : null,
        ]);
    }

    /**
     * Reject a transfer that would take the SOURCE pond's stock below zero.
     *
     * Runs AFTER the basic rules so a missing pond/quantity reports the simpler
     * error first. Uses FishStockService — the single definition of live stock.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->has('from_pond_id')
                || $validator->errors()->has('quantity')
                || $validator->errors()->has('to_pond_id')) {
                return;
            }

            $from = Pond::find($this->input('from_pond_id'));

            if ($from === null) {
                return;
            }

            $quantity = (int) $this->input('quantity');
            $available = app(FishStockService::class)->currentStock($from);

            if ($quantity > $available) {
                $validator->errors()->add(
                    'quantity',
                    "Pond \"{$from->name}\" holds only {$available} fish, so {$quantity} cannot be transferred out."
                );
            }
        });
    }
}