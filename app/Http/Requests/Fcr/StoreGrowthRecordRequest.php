<?php

namespace App\Http\Requests\Fcr;

use App\Models\GrowthRecord;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a growth sample.
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:growth.create` on the route AND authorize() here.
 *   - `pond_id` validated with Rule::exists — never trusted.
 *
 * A weight is REQUIRED and must be > 0: a sample with no weight measures nothing,
 * and storing 0 would feed a false "no growth" into FCR
 * (docs/BUSINESS_LOGIC.md §1).
 */
class StoreGrowthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('growth.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'sampled_on' => ['required', 'date', 'before_or_equal:today'],

            // Average weight of one fish, in grams.
            'avg_weight_g' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999.99'],

            // How many fish were weighed — optional sampling context.
            'sample_size' => ['nullable', 'integer', 'gt:0', 'max:1000000'],

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
            'sampled_on.required' => 'A sampling date is required.',
            'sampled_on.before_or_equal' => 'The sampling date cannot be in the future.',
            'avg_weight_g.required' => 'An average weight is required.',
            'avg_weight_g.gt' => 'The average weight must be greater than zero.',
            'avg_weight_g.decimal' => 'The weight may have at most 2 decimal places.',
            'sample_size.gt' => 'The sample size must be greater than zero.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'sample_size' => $this->filled('sample_size') ? $this->input('sample_size') : null,
        ]);
    }
}
