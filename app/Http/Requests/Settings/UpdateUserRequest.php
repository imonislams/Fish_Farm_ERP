<?php

namespace App\Http\Requests\Settings;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Update a user.
 *
 * SECURITY (docs/PERMISSIONS.md §3):
 *   - Authorization is enforced by `permission:users.update` on the route.
 *   - The target user comes from ROUTE MODEL BINDING, so an attacker cannot
 *     swap the id in the form.
 *   - A user cannot change their own role or deactivate themselves — that would
 *     be privilege escalation / self-lockout. Enforced in `withValidator`.
 *   - Password is optional: blank means "leave unchanged".
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target?->id),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
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
            'email.unique' => 'That email address is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }

    /**
     * Prevent privilege escalation and self-lockout.
     *
     * A user editing THEMSELVES must not be able to change their own role or
     * deactivate their account. Another administrator with `users.update` can
     * still do it for them.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $target = $this->route('user');
            $actor = $this->user();

            if (! $target || ! $actor || $target->id !== $actor->id) {
                return;
            }

            // Own role unchanged?
            $currentRoleId = $target->roles->first()?->id;
            if ($this->filled('role_id') && (int) $this->input('role_id') !== (int) $currentRoleId) {
                $validator->errors()->add('role_id', 'You cannot change your own role.');
            }

            // Own account must stay active.
            if (! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'You cannot deactivate your own account.');
            }
        });
    }
}
