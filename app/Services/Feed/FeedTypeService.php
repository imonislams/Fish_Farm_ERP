<?php

namespace App\Services\Feed;

use App\Models\FeedType;

/**
 * Feed type (master data) business logic.
 *
 * The important rule lives in delete(): a feed type that is referenced by any
 * purchase, usage or adjustment must never be deleted silently. The database
 * enforces this with `restrictOnDelete`, and this service turns the situation
 * into a clear application error. The guard is here — not only in the UI —
 * because this is the actual write path (docs/ARCHITECTURE.md §3).
 */
class FeedTypeService
{
    /**
     * Create a feed type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FeedType
    {
        return FeedType::create([
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'protein_percent' => $data['protein_percent'] ?? null,
            'unit' => $data['unit'] ?? null,
            'package_weight_kg' => $data['package_weight_kg'] ?? null,
            'default_unit_cost' => $data['default_unit_cost'] ?? null,
            'low_stock_level_kg' => $data['low_stock_level_kg'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Update a feed type.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(FeedType $type, array $data): FeedType
    {
        $type->fill([
            'name' => $data['name'],
            'brand' => $data['brand'] ?? null,
            'protein_percent' => $data['protein_percent'] ?? null,
            'unit' => $data['unit'] ?? null,
            'package_weight_kg' => $data['package_weight_kg'] ?? null,
            'default_unit_cost' => $data['default_unit_cost'] ?? null,
            'low_stock_level_kg' => $data['low_stock_level_kg'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'description' => $data['description'] ?? null,
        ])->save();

        return $type->refresh();
    }

    /**
     * Delete a feed type.
     *
     * Refuses when any movement references it, so stock history is never
     * orphaned. The message states the counts so the user knows why. Mark the
     * type inactive instead.
     *
     * @throws \DomainException when the feed type is in use
     */
    public function delete(FeedType $type): void
    {
        $purchases = $type->purchases()->count();
        $usages = $type->usages()->count();
        $adjustments = $type->adjustments()->count();
        $total = $purchases + $usages + $adjustments;

        if ($total > 0) {
            throw new \DomainException(
                "\"{$type->name}\" is used by {$total} record(s) "
                    . "({$purchases} purchase, {$usages} usage, {$adjustments} adjustment) "
                    . 'and cannot be deleted. Mark it inactive instead.'
            );
        }

        $type->delete();
    }
}
