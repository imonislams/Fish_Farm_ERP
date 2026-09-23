<?php

namespace App\Http\Requests\Fcr;

use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a pond inspection.
 *
 * The target comes from ROUTE MODEL BINDING, so the id in the form cannot be
 * swapped. Editing does NOT advance the pond's inspection schedule — an edit
 * corrects a record, it is not a new inspection.
 */
class UpdateInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('fcr.inspection.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'inspected_on' => ['required', 'date', 'before_or_equal:today'],
            'inspected_by' => ['nullable', 'string', 'max:150'],
            'health_status' => [
                'required',
                'string',
                Rule::in(array_keys(config('fcr.health_statuses', []))),
            ],
            'action_taken' => ['nullable', 'string', 'max:2000'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (config('fcr.parameters', []) as $key => $meta) {
            $decimals = (int) ($meta['decimals'] ?? 2);

            $rules[$key] = [
                'nullable',
                'numeric',
                "decimal:0,{$decimals}",
                'min:' . ($meta['min'] ?? 0),
                'max:' . ($meta['max'] ?? 100000),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pond_id.required' => 'Please select a pond.',
            'inspected_on.required' => 'An inspection date is required.',
            'health_status.required' => 'Please record the pond health status.',
            'health_status.in' => 'The selected health status is not valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'inspected_by' => $this->filled('inspected_by') ? trim((string) $this->input('inspected_by')) : null,
            'action_taken' => $this->filled('action_taken') ? trim((string) $this->input('action_taken')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ];

        foreach (array_keys(config('fcr.parameters', [])) as $key) {
            $merge[$key] = $this->filled($key) ? $this->input($key) : null;
        }

        $this->merge($merge);
    }
}
