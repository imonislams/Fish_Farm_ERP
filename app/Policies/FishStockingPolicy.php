<?php

namespace App\Policies;

use App\Models\FishStocking;
use App\Models\User;

/**
 * FishStocking policy — per-record authorization for stock-IN movements.
 *
 * Route `permission:fish.stock` middleware blocks a user without the permission
 * from reaching the page; this policy is the second layer and is what prevents
 * access by changing an id in the URL (docs/PERMISSIONS.md §3).
 *
 * The Super Admin bypass lives solely in `Gate::before()` — never here.
 */
class FishStockingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fish.view');
    }

    public function view(User $user, FishStocking $stocking): bool
    {
        return $user->hasPermission('fish.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fish.stock');
    }

    public function update(User $user, FishStocking $stocking): bool
    {
        return $user->hasPermission('fish.stock');
    }

    /**
     * Deleting a stocking removes stock, so it is refused when the pond can no
     * longer cover it. That business guard lives in FishStockService (the write
     * path); this method only decides whether the user may attempt it.
     */
    public function delete(User $user, FishStocking $stocking): bool
    {
        return $user->hasPermission('fish.stock');
    }
}
