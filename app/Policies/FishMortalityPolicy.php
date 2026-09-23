<?php

namespace App\Policies;

use App\Models\FishMortality;
use App\Models\User;

/**
 * FishMortality policy — per-record authorization for stock-OUT mortality records.
 *
 * The non-negative stock guard is a business rule and lives in FishStockService
 * (the write path); this policy only decides whether the user may act.
 */
class FishMortalityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fish.view');
    }

    public function view(User $user, FishMortality $mortality): bool
    {
        return $user->hasPermission('fish.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fish.mortality');
    }

    public function update(User $user, FishMortality $mortality): bool
    {
        return $user->hasPermission('fish.mortality');
    }

    public function delete(User $user, FishMortality $mortality): bool
    {
        return $user->hasPermission('fish.mortality');
    }
}
