<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a role.
 *
 * SECURITY (docs/PERMISSIONS.md §3):
 *   - Authorization is enforced by `permission:roles.create` on the route.
 *   - `name` becomes a stable machine key; it is restricted to safe characters
 *     so it can never be used to inject anything into a permission string.
 *   - `permissions` are validated as EXISTING permission names — an unknown
 *     value is rejected rather than silently stored.
 */
class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('roles', 'name'),
            ],
            'label' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A machine name is required (e.g. pond_supervisor).',
            'name.alpha_dash' => 'The machine name may only contain letters, numbers, dashes and underscores.',
            'name.unique' => 'A role with that machine name already exists.',
            'label.required' => 'A display label is required.',
            'permissions.*.exists' => 'One of the selected permissions is not valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge([
                'name' => strtolower(str_replace([' ', '-'], '_', trim((string) $this->input('name')))),
            ]);
        }
    }
}
