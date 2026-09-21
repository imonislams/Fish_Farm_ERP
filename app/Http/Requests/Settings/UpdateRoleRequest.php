<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a role (details and/or permission assignment).
 *
 * SECURITY (docs/PERMISSIONS.md §3):
 *   - Authorization is enforced by `permission:roles.update` and, for the
 *     permission matrix, additionally by `permissions.manage` on the route.
 *   - The target role comes from ROUTE MODEL BINDING.
 *   - A SYSTEM role (`is_system`) may not have its machine name changed, and the
 *     Super Admin role may not be stripped of permissions — both would be a
 *     lockout. Enforced in `withValidator`.
 */
class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.update') ?? false;
    }

       /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('roles', 'name')->ignore($role?->id),
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
            'name.alpha_dash' => 'The machine name may only contain letters, numbers, dashes and underscores.',
            'name.unique' => 'A role with that machine name already exists.',
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

    /**
     * Protect against lockout.
     *
     *  - A system role's machine name is immutable (it is referenced in code,
     *    e.g. User::hasRole('super_admin')).
     *  - Super Admin must keep at least one permission, so the catalogue cannot
     *    be emptied out from under the only administrative account.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $role = $this->route('role');

            if (! $role) {
                return;
            }

            if ($role->is_system && $this->filled('name') && $this->input('name') !== $role->name) {
                $validator->errors()->add(
                    'name',
                    'The machine name of a system role cannot be changed.'
                );
            }

            if ($role->name === 'super_admin' && count((array) $this->input('permissions', [])) === 0) {
                $validator->errors()->add(
                    'permissions',
                    'Super Admin must retain its permissions.'
                );
            }
        });
    }
}
