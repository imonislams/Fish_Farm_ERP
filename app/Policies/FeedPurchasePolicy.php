<?php

namespace App\Policies;

use App\Models\FeedPurchase;
use App\Models\User;

/**
 * FeedPurchase policy — per-record authorization for stock-IN purchases.
 *
 * Route `permission:feed.purchase` middleware blocks a user without the
 * permission from reaching the page; this policy is the second layer, and is what
 * prevents access by changing an id in the URL (docs/PERMISSIONS.md §3).
 *
 * The Super Admin bypass lives solely in `Gate::before()` — never here.
 */
class FeedPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function view(User $user, FeedPurchase $purchase): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('feed.purchase');
    }

    public function update(User $user, FeedPurchase $purchase): bool
    {
        return $user->hasPermission('feed.purchase');
    }

    /**
     * Deleting a purchase removes stock, so it is refused when the feed can no
     * longer cover it. That business guard lives in FeedStockService (the write
     * path); this method only decides whether the user may attempt it.
     */
    public function delete(User $user, FeedPurchase $purchase): bool
    {
        return $user->hasPermission('feed.purchase');
    }
}
