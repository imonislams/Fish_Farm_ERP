<?php

namespace App\Http\Requests\Fcr;

use App\Models\GrowthRecord;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a growth sample.
 *
 * The target comes from ROUTE MODEL BINDING, so the id in the form cannot be
 * swapped.
 */
class UpdateGrowthRecordRequest extends FormRequest
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
            'avg_weight_g' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999.99'],
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
            'avg_weight_g.required' => 'An average weight is required.',
            'avg_weight_g.gt' => 'The average weight must be greater than zero.',
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
