<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Company settings validation.
 *
 * Version 1 is SINGLE-COMPANY: this edits "the" company, so there is no
 * company_id in the request — the controller resolves it. Never trust an ID
 * from the form. See docs/PERMISSIONS.md §3.
 */
class UpdateCompanyRequest extends FormRequest
{
    /**
     * Authorization is enforced by the `permission:company.update` middleware on
     * the route, and re-asserted here so the request is safe on its own.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('company.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'nullable', 'string', 'max:50', 'alpha_dash',
                Rule::unique('companies', 'code')->ignore($this->route('company')?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'timezone' => ['required', 'string', 'max:64', 'timezone'],
            'status' => ['required', Rule::in(['active', 'inactive'])],

            // Logo is optional. Images only, size capped.
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The company / farm name is required.',
            'currency.size' => 'Use a 3-letter currency code, e.g. BDT.',
            'timezone.timezone' => 'Please choose a valid timezone.',
            'logo.image' => 'The logo must be an image file.',
            'logo.max' => 'The logo must not be larger than 2 MB.',
        ];
    }

    /** Normalise the code to uppercase so uniqueness is case-insensitive. */
    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper((string) $this->input('code'))]);
        }
    }
}
