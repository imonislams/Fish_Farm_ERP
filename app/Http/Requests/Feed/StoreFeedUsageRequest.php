<?php

namespace App\Http\Requests\Feed;

use App\Models\FeedType;
use App\Models\Pond;
use App\Services\Feed\FeedStockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a feed usage (feed stock OUT, into a pond).
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization is enforced by `permission:feed.usage` on the route AND
 *     re-checked in authorize() here.
 *   - `pond_id` / `feed_type_id` validated with Rule::exists — never trusted.
 *
 * STOCK GUARD (docs/BUSINESS_LOGIC.md §2):
 *   Feed stock can never go negative. A usage exceeding the feed type's current
 *   stock is rejected here for a clean field-level message, and AGAIN in
 *   FeedStockService (the write path) so the rule holds even if this is bypassed.
 */
class StoreFeedUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('feed.usage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pond_id' => ['required', 'integer', Rule::exists(Pond::class, 'id')],
            'feed_type_id' => ['required', 'integer', Rule::exists(FeedType::class, 'id')],
            'quantity_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999.999'],
            'used_on' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:2000'],
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
            'feed_type_id.required' => 'Please select a feed type.',
            'feed_type_id.exists' => 'The selected feed type does not exist.',
            'quantity_kg.required' => 'A quantity is required.',
            'quantity_kg.gt' => 'The quantity must be greater than zero.',
            'quantity_kg.decimal' => 'The quantity may have at most 3 decimal places.',
            'used_on.required' => 'A date is required.',
            'used_on.before_or_equal' => 'The date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    /**
     * Reject a usage that would take the feed type's stock below zero.
     *
     * Uses FeedStockService — the single definition of current feed stock.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->has('feed_type_id') || $validator->errors()->has('quantity_kg')) {
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
                    "\"{$type->name}\" holds only {$available} kg, so {$quantity} kg cannot be used."
                );
            }
        });
    }
}
