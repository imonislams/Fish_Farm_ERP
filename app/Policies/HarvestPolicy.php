<?php

namespace App\Policies;

use App\Models\Harvest;
use App\Models\User;

/**
 * Harvest policy — per-record authorization for stock-OUT harvest records.
 *
 * The non-negative stock guard is a business rule and lives in FishStockService
 * (the write path); this policy only decides whether the user may act.
 */
class HarvestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fish.view');
    }

    public function view(User $user, Harvest $harvest): bool
    {
        return $user->hasPermission('fish.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fish.harvest');
    }

    public function update(User $user, Harvest $harvest): bool
    {
        return $user->hasPermission('fish.harvest');
    }

    public function delete(User $user, Harvest $harvest): bool
    {
        return $user->hasPermission('fish.harvest');
    }
}
