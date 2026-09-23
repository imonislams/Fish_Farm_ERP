<?php

namespace App\Http\Requests\Sales;

use App\Models\Customer;
use App\Models\FishSpecies;
use App\Models\Pond;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create a sale (header + line items).
 *
 * SECURITY: `permission:sales.create` on the route plus the check here. Customer,
 * species and pond are validated with Rule::exists — never trusted.
 *
 * DERIVED VALUES (`total`, `due_amount`, `status`, each `line_total`) are NOT
 * accepted from input: SalesService computes them, so the stored figures can never
 * contradict the lines.
 */
class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists(Customer::class, 'id')],
            'invoice_no' => ['required', 'string', 'max:60', Rule::unique('sales', 'invoice_no')],
            'sale_date' => ['required', 'date', 'before_or_equal:today'],
            'discount' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:999999.99'],
            'paid_amount' => ['nullable', 'numeric', 'gte:0', 'decimal:0,2', 'max:9999999.99'],
            'note' => ['nullable', 'string', 'max:2000'],

            // At least one line, each with a price and a quantity or a weight.
            'items' => ['required', 'array', 'min:1'],
            'items.*.fish_species_id' => ['nullable', 'integer', Rule::exists(FishSpecies::class, 'id')],
            'items.*.pond_id' => ['nullable', 'integer', Rule::exists(Pond::class, 'id')],
            'items.*.quantity' => ['nullable', 'integer', 'gt:0', 'max:1000000'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'gt:0', 'decimal:0,3', 'max:999999.999'],
            'items.*.unit_price' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999.99'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'invoice_no.required' => 'An invoice number is required.',
            'invoice_no.unique' => 'This invoice number is already in use.',
            'sale_date.required' => 'A sale date is required.',
            'sale_date.before_or_equal' => 'The sale date cannot be in the future.',
            'items.required' => 'A sale needs at least one line item.',
            'items.min' => 'A sale needs at least one line item.',
            'items.*.unit_price.required' => 'Every line needs a unit price.',
            'items.*.unit_price.gt' => 'The unit price must be greater than zero.',
            'items.*.weight_kg.gt' => 'The weight must be greater than zero.',
            'items.*.quantity.gt' => 'The quantity must be greater than zero.',
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
     * Every line must carry either a weight or a count.
     *
     * A sale of fish is normally by weight; a by-count sale is allowed but the
     * line must say which it is, otherwise the line total would be `0 × price`.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ((array) $this->input('items', []) as $index => $item) {
                $weight = $item['weight_kg'] ?? null;
                $quantity = $item['quantity'] ?? null;

                $hasWeight = $weight !== null && $weight !== '' && (float) $weight > 0;
                $hasCount = $quantity !== null && $quantity !== '' && (int) $quantity > 0;

                if (! $hasWeight && ! $hasCount) {
                    $validator->errors()->add(
                        "items.{$index}.weight_kg",
                        'Enter a weight (kg) or a quantity for this line.'
                    );
                }
            }
        });
    }
}
