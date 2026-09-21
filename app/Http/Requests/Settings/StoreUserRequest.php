<?php

namespace App\Http\Requests\Settings;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Create a user.
 *
 * SECURITY (docs/PERMISSIONS.md §3):
 *   - Authorization is enforced by `permission:users.create` on the route.
 *   - `role_id` is validated against the roles table — never trusted directly.
 *   - `company_id` is NOT accepted from the request; the controller assigns the
 *     single company. A user cannot move themselves to another company.
 *   - There is no `is_system`/permission field here: a user cannot escalate.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_id' => [
                'required',
                Rule::exists(Role::class, 'id'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The user name is required.',
            'email.required' => 'An email address is required.',
            'email.unique' => 'That email address is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role_id.required' => 'Please assign a role.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }
}
