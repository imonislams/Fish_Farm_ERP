<?php

namespace App\Http\Requests\Feed;

use App\Models\FeedType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a feed purchase (feed stock IN).
 *
 * SECURITY (docs/PERMISSIONS.md §2):
 *   - Authorization is enforced by `permission:feed.purchase` on the route AND
 *     re-checked in authorize() here.
 *   - `feed_type_id` is validated with Rule::exists — never trusted.
 *   - `total_cost` is NOT accepted from input: the service derives it from the
 *     quantity and unit cost, so the stored figure is always consistent.
 *
 * A purchase INCREASES stock, so there is no non-negative guard to apply.
 */
class StoreFeedPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('feed.purchase') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'feed_type_id' => ['required', 'integer', Rule::exists(FeedType::class, 'id')],

            // Always kilograms, 3 decimals.
            'quantity_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999.999'],

            'unit_cost' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:99999.99'],

            'purchased_on' => ['required', 'date', 'before_or_equal:today'],

            'invoice_no' => ['nullable', 'string', 'max:100'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'paid_amount' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:9999.99'],
            'note' => ['nullable', 'string', 'max:2000'],
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
            'quantity_kg.required' => 'A quantity is required.',
            'quantity_kg.gt' => 'The quantity must be greater than zero.',
            'quantity_kg.decimal' => 'The quantity may have at most 3 decimal places.',
            'unit_cost.gte' => 'The unit cost cannot be negative.',
            'purchased_on.required' => 'A purchase date is required.',
            'purchased_on.before_or_equal' => 'The purchase date cannot be in the future.',
            'paid_amount.gte' => 'The paid amount cannot be negative.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'invoice_no' => $this->filled('invoice_no') ? trim((string) $this->input('invoice_no')) : null,
            'supplier_name' => $this->filled('supplier_name') ? trim((string) $this->input('supplier_name')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'unit_cost' => $this->filled('unit_cost') ? $this->input('unit_cost') : null,
            'paid_amount' => $this->filled('paid_amount') ? $this->input('paid_amount') : null,
        ]);
    }

    /**
     * A paid amount greater than the line cost is almost always a typo — warn
     * rather than silently accept an over-payment on this purchase line.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->has('paid_amount') || $validator->errors()->has('quantity_kg')) {
                return;
            }

            $unitCost = $this->input('unit_cost');
            $paid = $this->input('paid_amount');

            if ($unitCost === null || $paid === null) {
                return;
            }

            $total = (float) $this->input('quantity_kg') * (float) $unitCost;

            if ((float) $paid > $total + 0.01) {
                $validator->errors()->add(
                    'paid_amount',
                    'The paid amount is greater than the purchase total (' . number_format($total, 2) . ').'
                );
            }
        });
    }
}
