<?php

namespace App\Http\Requests\Feed;

use App\Models\Feeding;
use App\Models\FeedingSchedule;
use App\Models\FeedType;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record an ACTUAL feeding.
 *
 * The feed-stock guard is authoritative in FeedStockService; this request only
 * shapes and validates the input. A 'skipped' feeding consumes nothing, so its
 * consumed quantity may be zero.
 */
class StoreFeedingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('feed.feeding') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'feed_type_id' => ['required', 'integer', Rule::exists(FeedType::class, 'id')],
            'feeding_schedule_id' => ['nullable', 'integer', Rule::exists(FeedingSchedule::class, 'id')],
            'planned_quantity_kg' => ['nullable', 'numeric', 'gte:0', 'decimal:0,3', 'max:99999.999'],
            'consumed_quantity_kg' => ['required', 'numeric', 'gte:0', 'decimal:0,3', 'max:99999.999'],
            'fed_on' => ['required', 'date', 'before_or_equal:today'],
            'fed_at' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'string', Rule::in(array_keys(config('finance.feeding_statuses', [])))],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * A non-skipped feeding must have actually consumed feed.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $status = (string) $this->input('status');
            $consumed = (float) $this->input('consumed_quantity_kg', 0);

            if ($status !== Feeding::STATUS_SKIPPED && $consumed <= 0) {
                $validator->errors()->add(
                    'consumed_quantity_kg',
                    'Enter how much feed was actually given (or mark the meal as skipped).',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'pond_id.required' => 'Choose the pond.',
            'feed_type_id.required' => 'Choose the feed type.',
            'fed_on.before_or_equal' => 'The feeding date cannot be in the future.',
        ];
    }
}
