<?php

namespace App\Services\Pond;

use App\Models\PondType;

/**
 * Pond type (master data) business logic.
 *
 * The controller stays thin; every write goes through here (docs/ARCHITECTURE.md §3).
 *
 * THE IMPORTANT RULE lives in delete(): a pond type that is currently used by one
 * or more ponds must never be deleted silently. The database enforces this with
 * `restrictOnDelete`, and this service turns the situation into a clear
 * application error. The guard is here — not only in the UI — because this is the
 * actual write path (docs/ARCHITECTURE.md §3 "Service — write-path guards").
 */
class PondTypeService
{
    /**
     * Create a pond type.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PondType
    {
        return PondType::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * Update a pond type.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PondType $type, array $data): PondType
    {
        $type->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ])->save();

        return $type->refresh();
    }

    /**
     * Delete a pond type.
     *
     * Refuses when ponds still reference it, so no pond is ever left pointing at
     * a type that no longer exists. The message names the ponds so the user knows
     * what to reassign.
     *
     * @throws \DomainException when the type is in use
     */
    public function delete(PondType $type): void
    {
        $pondCount = $type->ponds()->count();

        if ($pondCount > 0) {
            throw new \DomainException(
                "\"{$type->name}\" is used by {$pondCount} pond(s) and cannot be deleted. "
                    . 'Reassign those ponds to another type first, or mark this type inactive.'
            );
        }

        $type->delete();
    }
}
