<?php

namespace App\Http\Requests\Fish;

use App\Models\FishBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a fish batch's editable header (status / end date / note).
 *
 * The quantity is never edited here — it lives in the movement records, so the
 * batch can never become a second source of truth.
 */
class UpdateFishBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('fish.batch.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(config('finance.batch_statuses', [])))],
            'ended_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Choose the batch status.',
        ];
    }
}
