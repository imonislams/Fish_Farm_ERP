<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Update the authenticated user's OWN profile.
 *
 * SECURITY (docs/PERMISSIONS.md §3, PROJECT.md §22):
 *   - The target is ALWAYS `$this->user()` — the form cannot address another
 *     account, so there is no ID to tamper with.
 *   - Only name / email / password are accepted. There is deliberately NO
 *     `role_id`, `is_active` or `company_id` field: a user must never be able
 *     to change their own role, status or company. Those fields are not in
 *     `rules()`, so mass assignment cannot pick them up.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            // Password is optional — blank means "keep the current one".
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Your name is required.',
            'email.unique' => 'That email address is already in use.',
            'current_password.current_password' => 'Your current password is incorrect.',
            'current_password.required_with' => 'Enter your current password to set a new one.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }

    /** Is the user changing their password? */
    public function wantsPasswordChange(): bool
    {
        return $this->filled('password');
    }
}
