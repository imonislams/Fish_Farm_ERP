<?php

namespace App\Http\Requests\Pond;

use App\Models\Pond;
use App\Services\Fish\FishStockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a mortality from within the Pond Ledger module (New death records).
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:pond_ledger.mortality.create` on the route AND
 *     authorize() here.
 *   - `pond_id` validated with Rule::exists — never trusted.
 *   - `cause` validated against config/fish.php so the stored key is one the UI
 *     can render.
 *
 * STOCK GUARD (docs/BUSINESS_LOGIC.md §2):
 *   Stock can never go negative. A mortality exceeding the pond's live stock is
 *   rejected here for a clean field-level message, and AGAIN in FishStockService
 *   (the write path) so the rule holds even if this check is bypassed.
 */
class StoreMortalityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pond_ledger.mortality.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'quantity' => ['required', 'integer', 'gt:0', 'max:100000'],
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],

            // Optional — forcing a cause would create fake precision.
            'cause' => ['nullable', 'string', Rule::in(array_keys(config('fish.mortality_causes', [])))],

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
            'quantity.required' => 'A quantity is required.',
            'quantity.integer' => 'The quantity must be a whole number of fish.',
            'quantity.gt' => 'The quantity must be greater than zero.',
            'recorded_on.required' => 'A date is required.',
            'recorded_on.before_or_equal' => 'The date cannot be in the future.',
            'cause.in' => 'The selected cause is not valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cause' => $this->filled('cause') ? $this->input('cause') : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    /**
     * Reject a mortality that would take the pond's stock below zero.
     *
     * Runs AFTER the basic rules so a missing pond/quantity reports the simpler
     * error first. Uses FishStockService — the single definition of live stock.
     */
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
                    "Pond \"{$pond->name}\" holds only {$available} fish, so {$quantity} cannot be recorded as mortality."
                );
            }
        });
    }
}
