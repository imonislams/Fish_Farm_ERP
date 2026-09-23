<?php

namespace App\Http\Requests\Supplier;

use App\Models\FeedType;
use App\Models\FishSpecies;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record a purchase from a supplier (header + line items).
 *
 * SECURITY: `permission:supplier.purchase.create` on the route plus the check.
 *
 * DERIVED VALUES (`total`, `due_amount`, `status`, each `line_total`) are computed
 * by PurchaseService, never accepted from input.
 */
class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplier.purchase.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $itemTypes = array_keys(config('finance.purchase_item_types', []));

        return [
            'supplier_id' => ['required', 'integer', Rule::exists(Supplier::class, 'id')],
            'invoice_no' => ['nullable', 'string', 'max:60'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'discount' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:999999.99'],
            'paid_amount' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:9999999.99'],
            'note' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'string', Rule::in($itemTypes)],
            'items.*.feed_type_id' => ['nullable', 'integer', Rule::exists(FeedType::class, 'id')],
            'items.*.fish_species_id' => ['nullable', 'integer', Rule::exists(FishSpecies::class, 'id')],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999999.999'],
            'items.*.unit_cost' => ['required', 'numeric', 'gte:0', 'decimal:0,2', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'purchase_date.required' => 'A purchase date is required.',
            'purchase_date.before_or_equal' => 'The purchase date cannot be in the future.',
            'items.required' => 'A purchase needs at least one line item.',
            'items.min' => 'A purchase needs at least one line item.',
            'items.*.item_type.required' => 'Every line needs a type.',
            'items.*.item_type.in' => 'The selected item type is not valid.',
            'items.*.quantity.required' => 'Every line needs a quantity.',
            'items.*.quantity.gt' => 'The quantity must be greater than zero.',
            'items.*.unit_cost.required' => 'Every line needs a unit cost.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'invoice_no' => $this->filled('invoice_no') ? trim((string) $this->input('invoice_no')) : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'discount' => $this->filled('discount') ? $this->input('discount') : 0,
            'paid_amount' => $this->filled('paid_amount') ? $this->input('paid_amount') : 0,
        ]);
    }

    /**
     * A `feed` line must name a feed type; a `fingerlings` line a species.
     * Otherwise the line would record a cost against nothing.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ((array) $this->input('items', []) as $index => $item) {
                $type = $item['item_type'] ?? null;

                if ($type === 'feed' && empty($item['feed_type_id'])) {
                    $validator->errors()->add(
                        "items.{$index}.feed_type_id",
                        'Choose the feed type for this line.'
                    );
                }

                if ($type === 'fingerlings' && empty($item['fish_species_id'])) {
                    $validator->errors()->add(
                        "items.{$index}.fish_species_id",
                        'Choose the species for this line.'
                    );
                }
            }
        });
    }
}
