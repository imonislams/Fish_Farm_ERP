<?php

namespace App\Policies;

use App\Models\Feeding;
use App\Models\User;

/**
 * Feeding (actual meal) policy — per-record authorization.
 *
 * The stock movement a feeding produces is owned by FeedStockService; this policy
 * only decides whether the user may act.
 */
class FeedingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function view(User $user, Feeding $feeding): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('feed.feeding');
    }

    public function update(User $user, Feeding $feeding): bool
    {
        return $user->hasPermission('feed.feeding');
    }

    public function delete(User $user, Feeding $feeding): bool
    {
        return $user->hasPermission('feed.feeding');
    }
}
