<?php

namespace App\Http\Requests\Fish;

use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a fish batch (stocking cycle).
 *
 * `initial_quantity` is optional: when given (with a weight), it is recorded as a
 * REAL stocking movement through FishStockService, so the batch's first movement
 * is a genuine stock row — the batch carries no separate quantity (single source
 * of truth).
 */
class StoreFishBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('fish.batch.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'fish_species_id' => ['required', 'integer', Rule::exists(FishSpecies::class, 'id')],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('fish_batches', 'code')],
            'started_on' => ['required', 'date', 'before_or_equal:today'],
            'initial_quantity' => ['nullable', 'integer', 'gte:0', 'max:100000000'],
            'avg_weight_g' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999.99'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:9999999.99'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', Rule::in(array_keys(config('finance.batch_statuses', [])))],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * A recorded opening weight only makes sense with an opening quantity.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $quantity = (int) $this->input('initial_quantity', 0);

            if ($quantity <= 0 && $this->filled('avg_weight_g')) {
                $validator->errors()->add(
                    'initial_quantity',
                    'Enter the initial quantity for the recorded average weight.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'pond_id.required' => 'Choose the pond for this batch.',
            'fish_species_id.required' => 'Choose the species for this batch.',
            'started_on.before_or_equal' => 'The start date cannot be in the future.',
        ];
    }
}
