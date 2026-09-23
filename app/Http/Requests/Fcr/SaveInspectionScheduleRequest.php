<?php

namespace App\Http\Requests\Fcr;

use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or update a pond's inspection schedule.
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:fcr.schedule.manage` on the route AND here.
 *   - `pond_id` / `frequency` validated against the exists rule and the catalogue.
 *
 * `next_due_on` is NOT accepted from input: it is derived by
 * InspectionScheduleService from `last_completed_on` (or today) plus the
 * frequency interval, so the due date can never disagree with the frequency
 * (docs/BUSINESS_LOGIC.md §1).
 */
class SaveInspectionScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('fcr.schedule.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],

            'frequency' => [
                'required',
                'string',
                Rule::in(array_keys(config('fcr.frequencies', []))),
            ],

            // Optional: when a pond was last inspected before this system was used.
            'last_completed_on' => ['nullable', 'date', 'before_or_equal:today'],

            'is_active' => ['nullable', 'boolean'],
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
            'frequency.required' => 'Please choose how often the pond is inspected.',
            'frequency.in' => 'The selected frequency is not valid.',
            'last_completed_on.before_or_equal' => 'The last completed date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'last_completed_on' => $this->filled('last_completed_on') ? $this->input('last_completed_on') : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
