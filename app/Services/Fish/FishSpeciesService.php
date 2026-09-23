<?php

namespace App\Services\Fish;

use App\Models\FishSpecies;

/**
 * Fish species (master data) business logic.
 *
 * The important rule lives in delete(): a species that is currently used by any
 * stocking or harvest record must never be deleted silently. The database
 * enforces this with `restrictOnDelete`, and this service turns the situation
 * into a clear application error. The guard is here — not only in the UI —
 * because this is the actual write path (docs/ARCHITECTURE.md §3).
 */
class FishSpeciesService
{
    /**
     * Create a species.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FishSpecies
    {
        return FishSpecies::create([
            'name' => $data['name'],
            'local_name' => $data['local_name'] ?? null,
            'scientific_name' => $data['scientific_name'] ?? null,
            'default_price_per_kg' => $data['default_price_per_kg'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * Update a species.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(FishSpecies $species, array $data): FishSpecies
    {
        $species->fill([
            'name' => $data['name'],
            'local_name' => $data['local_name'] ?? null,
            'scientific_name' => $data['scientific_name'] ?? null,
            'default_price_per_kg' => $data['default_price_per_kg'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'description' => $data['description'] ?? null,
        ])->save();

        return $species->refresh();
    }

    /**
     * Delete a species.
     *
     * Refuses when any stocking or harvest record references it, so stock
     * history is never orphaned. The message states the count so the user knows
     * why. Mark the species inactive instead to stop it being offered.
     *
     * @throws \DomainException when the species is in use
     */
    public function delete(FishSpecies $species): void
    {
        $stockings = $species->stockings()->count();
        $harvests = $species->harvests()->count();
        $total = $stockings + $harvests;

        if ($total > 0) {
            throw new \DomainException(
                "\"{$species->name}\" is used by {$total} record(s) "
                    . "({$stockings} stocking, {$harvests} harvest) and cannot be deleted. "
                    . 'Mark it inactive instead.'
            );
        }

        $species->delete();
    }
}
