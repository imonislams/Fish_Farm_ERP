<?php

namespace App\Http\Requests\Feed;

use App\Models\FeedStockAdjustment;
use App\Models\FeedType;
use App\Services\Feed\FeedStockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a manual feed stock adjustment.
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization is enforced by `permission:feed.adjust` on the route AND
 *     re-checked in authorize() here.
 *   - `feed_type_id` validated with Rule::exists — never trusted.
 *   - `direction` and `reason` are validated against config/feed.php so the
 *     stored keys are ones the UI can render.
 *
 * BUSINESS RULE (docs/BUSINESS_LOGIC.md §2):
 *   Stock is never changed silently — a direction and a reason are REQUIRED.
 *   An OUT adjustment that would take stock negative is rejected here and again
 *   in FeedStockService (the write path).
 */
class StoreFeedAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('feed.adjust') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'feed_type_id' => ['required', 'integer', Rule::exists(FeedType::class, 'id')],

            'direction' => [
                'required',
                'string',
                Rule::in(array_keys(config('feed.adjustment_directions', []))),
            ],

            'quantity_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999.999'],

            // A reason is mandatory: an unexplained correction is not allowed.
            'reason' => [
                'required',
                'string',
                Rule::in(array_keys(config('feed.adjustment_reasons', []))),
            ],

            'note' => ['nullable', 'string', 'max:2000'],
            'adjusted_on' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'feed_type_id.required' => 'Please select a feed type.',
            'feed_type_id.exists' => 'The selected feed type does not exist.',
            'direction.required' => 'Please choose whether stock increases or decreases.',
            'direction.in' => 'The selected direction is not valid.',
            'quantity_kg.required' => 'A quantity is required.',
            'quantity_kg.gt' => 'The quantity must be greater than zero.',
            'quantity_kg.decimal' => 'The quantity may have at most 3 decimal places.',
            'reason.required' => 'A reason is required — stock is never changed without one.',
            'reason.in' => 'The selected reason is not valid.',
            'adjusted_on.required' => 'An adjustment date is required.',
            'adjusted_on.before_or_equal' => 'The adjustment date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    /** Reject an OUT adjustment that would take the feed type's stock negative. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (
                $validator->errors()->has('feed_type_id')
                || $validator->errors()->has('quantity_kg')
                || $validator->errors()->has('direction')
            ) {
                return;
            }

            if ($this->input('direction') !== FeedStockAdjustment::DIRECTION_OUT) {
                return;
            }

            $type = FeedType::find($this->input('feed_type_id'));

            if ($type === null) {
                return;
            }

            $quantity = (float) $this->input('quantity_kg');
            $available = app(FeedStockService::class)->currentStockKg($type);

            if ($quantity > $available) {
                $validator->errors()->add(
                    'quantity_kg',
                    "\"{$type->name}\" holds only {$available} kg, so {$quantity} kg cannot be removed."
                );
            }
        });
    }
}
