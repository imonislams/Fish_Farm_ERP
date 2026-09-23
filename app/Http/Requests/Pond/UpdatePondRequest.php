<?php

namespace App\Http\Requests\Pond;

use App\Models\Pond;
use App\Models\PondType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a pond.
 *
 * The target comes from ROUTE MODEL BINDING — the id in the form cannot be
 * swapped. `pond_number` remains editable (it is an operating label, not a
 * primary key), and its uniqueness check ignores the current record so saving an
 * unchanged form does not raise a false conflict.
 *
 * As on create, `is_active` is derived from `status` by the service and is not
 * accepted from input.
 */
class UpdatePondRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pond.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Pond|null $target */
        $target = $this->route('pond');

        return [
            'pond_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ponds', 'pond_number')->ignore($target?->id),
            ],
            'name' => ['required', 'string', 'max:150'],

            'pond_type_id' => [
                'required',
                'integer',
                Rule::exists(PondType::class, 'id'),
            ],

            'size' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999.999'],
            'size_unit' => ['required', 'string', Rule::in(array_keys(config('ponds.size_units', [])))],

            'depth' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3', 'max:99999.999'],
            'depth_unit' => ['nullable', 'required_with:depth', 'string', Rule::in(array_keys(config('ponds.depth_units', [])))],

            'location' => ['nullable', 'string', 'max:255'],
            'water_source' => ['nullable', 'string', 'max:100'],

            'status' => ['required', 'string', Rule::in(array_keys(config('ponds.statuses', [])))],

            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pond_number.required' => 'A pond number is required.',
            'pond_number.unique' => 'This pond number is already in use.',
            'name.required' => 'A pond name is required.',
            'pond_type_id.required' => 'Please select a pond type.',
            'pond_type_id.exists' => 'The selected pond type does not exist.',
            'size.required' => 'A size is required.',
            'size.gt' => 'The size must be greater than zero.',
            'size.decimal' => 'The size may have at most 3 decimal places.',
            'size_unit.required' => 'Please select a size unit.',
            'size_unit.in' => 'The selected size unit is not valid.',
            'depth.gt' => 'The depth must be greater than zero.',
            'depth.decimal' => 'The depth may have at most 3 decimal places.',
            'depth_unit.required_with' => 'Please select a depth unit when a depth is given.',
            'depth_unit.in' => 'The selected depth unit is not valid.',
            'status.required' => 'Please select a status.',
            'status.in' => 'The selected status is not valid.',
            'description.max' => 'The description may not be longer than 2000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pond_number' => $this->filled('pond_number')
                ? trim((string) $this->input('pond_number'))
                : null,
            'name' => $this->filled('name') ? trim((string) $this->input('name')) : null,
            'location' => $this->filled('location') ? trim((string) $this->input('location')) : null,
            'water_source' => $this->filled('water_source')
                ? trim((string) $this->input('water_source'))
                : null,
            'description' => $this->filled('description')
                ? trim((string) $this->input('description'))
                : null,
            'depth' => $this->filled('depth') ? $this->input('depth') : null,
            'depth_unit' => $this->filled('depth') && $this->filled('depth_unit')
                ? $this->input('depth_unit')
                : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach (['pond_number', 'name'] as $field) {
                if ($validator->errors()->has($field)) {
                    continue;
                }

                if (trim((string) $this->input($field, '')) === '') {
                    $validator->errors()->add(
                        $field,
                        $field === 'pond_number'
                            ? 'A pond number is required.'
                            : 'A pond name is required.'
                    );
                }
            }
        });
    }
}
