<?php

namespace App\Http\Requests\Pond;

use App\Models\Pond;
use App\Models\PondLedgerEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a pond ledger entry (manual).
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization: `permission:ledger.create` on the route AND authorize() here.
 *   - `pond_id` validated with Rule::exists — never trusted.
 *   - `entry_type` validated against config/ledger.php.
 *   - The `category` must belong to the CHOSEN type: a "Fish sale" category on a
 *     debit entry would mislabel the pond's costs, so the rule is cross-checked in
 *     withValidator().
 *   - `source_type` / `source_id` are NOT accepted from input: a manual entry is
 *     always recorded as source_type 'manual'. Generated entries come only from
 *     services.
 */
class StorePondLedgerEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('ledger.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],

            'entry_type' => [
                'required',
                'string',
                Rule::in(array_keys(config('ledger.entry_types', []))),
            ],

            // Checked against the categories of the chosen type in withValidator().
            'category' => ['required', 'string', 'max:60'],

            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999.99'],

            'entry_date' => ['required', 'date', 'before_or_equal:today'],

            'reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
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
            'entry_type.required' => 'Please choose whether this is income or an expense.',
            'entry_type.in' => 'The selected entry type is not valid.',
            'category.required' => 'Please select a category.',
            'amount.required' => 'An amount is required.',
            'amount.gt' => 'The amount must be greater than zero.',
            'amount.decimal' => 'The amount may have at most 2 decimal places.',
            'entry_date.required' => 'A date is required.',
            'entry_date.before_or_equal' => 'The date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference' => $this->filled('reference') ? trim((string) $this->input('reference')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    /**
     * The category must exist under the chosen entry type.
     *
     * Runs after the basic rules so a missing type reports the simpler error.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->has('entry_type') || $validator->errors()->has('category')) {
                return;
            }

            $type = (string) $this->input('entry_type');
            $category = (string) $this->input('category');

            $allowed = array_keys(config("ledger.categories.{$type}", []));

            if (! in_array($category, $allowed, true)) {
                $validator->errors()->add(
                    'category',
                    'The selected category is not valid for this entry type.'
                );
            }
        });
    }
}
