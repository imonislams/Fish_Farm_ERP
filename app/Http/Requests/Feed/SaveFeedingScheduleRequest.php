<?php

namespace App\Http\Requests\Feed;

use App\Models\FeedingSchedule;
use App\Models\FeedType;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or update a meal schedule (a PLAN — never moves feed stock).
 */
class SaveFeedingScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('feed.schedule.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'feed_type_id' => ['required', 'integer', Rule::exists(FeedType::class, 'id')],
            'scheduled_on' => ['required', 'date'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'planned_quantity_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:99999.999'],
            'recurrence' => ['required', 'string', Rule::in(array_keys(config('finance.feed_schedule_recurrences', [])))],
            'is_active' => ['boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'pond_id.required' => 'Choose the pond this meal is for.',
            'feed_type_id.required' => 'Choose the feed type.',
            'planned_quantity_kg.gt' => 'The planned quantity must be greater than zero.',
        ];
    }
}
