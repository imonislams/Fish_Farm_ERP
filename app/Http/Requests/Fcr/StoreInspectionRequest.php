<?php

namespace App\Http\Requests\Fcr;

use App\Models\Inspection;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a pond inspection.
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:fcr.inspection.create` on the route AND here.
 *   - `pond_id` validated with Rule::exists — never trusted.
 *   - `health_status` validated against config/fcr.php.
 *
 * MEASUREMENTS are optional and each is bound by the guard rails in
 * config/fcr.php. A parameter that is left blank is stored as NULL — never 0 —
 * because "not measured" and "measured zero" are different facts
 * (docs/BUSINESS_LOGIC.md §9 rule 6). The rules are built from the catalogue so a
 * new parameter cannot be added without validation following automatically.
 */
class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('fcr.inspection.create') ?? false;
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

        // One optional rule per parameter defined in the catalogue.
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
        $messages = [
            'pond_id.required' => 'Please select a pond.',
            'pond_id.exists' => 'The selected pond does not exist.',
            'inspected_on.required' => 'An inspection date is required.',
            'inspected_on.before_or_equal' => 'The inspection date cannot be in the future.',
            'health_status.required' => 'Please record the pond health status.',
            'health_status.in' => 'The selected health status is not valid.',
        ];

        foreach (config('fcr.parameters', []) as $key => $meta) {
            $label = $meta['label'] ?? $key;

            $messages["{$key}.numeric"] = "{$label} must be a number.";
            $messages["{$key}.decimal"] = "{$label} has too many decimal places.";
            $messages["{$key}.min"] = "{$label} cannot be below " . ($meta['min'] ?? 0) . '.';
            $messages["{$key}.max"] = "{$label} cannot be above " . ($meta['max'] ?? 0) . '.';
        }

        return $messages;
    }

    /**
     * Blank measurements become null so `nullable` behaves consistently and a
     * cleared field is not stored as "".
     */
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
