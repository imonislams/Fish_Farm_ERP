<?php

namespace App\Policies;

use App\Models\FishSpecies;
use App\Models\User;

/**
 * FishSpecies policy — per-record authorization for the species catalogue.
 *
 * Same two-layer model as the pond module: route `permission:` middleware plus
 * this per-record check. The "species in use cannot be deleted" rule is a
 * business rule, not an authorization rule, so it lives in FishSpeciesService
 * (the write path) and surfaces as a flash error rather than a 403.
 */
class FishSpeciesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fish.view');
    }

    public function view(User $user, FishSpecies $species): bool
    {
        return $user->hasPermission('fish.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fish.species.manage');
    }

    public function update(User $user, FishSpecies $species): bool
    {
        return $user->hasPermission('fish.species.manage');
    }

    public function delete(User $user, FishSpecies $species): bool
    {
        return $user->hasPermission('fish.species.manage');
    }
}
