<?php

namespace App\Policies;

use App\Models\FeedStockAdjustment;
use App\Models\User;

/**
 * FeedStockAdjustment policy — per-record authorization for manual corrections.
 *
 * The "stock can never go negative" and "a reason is always required" rules are
 * business rules and live in FeedStockService / the FormRequest (the write path);
 * this policy only decides whether the user may act.
 */
class FeedStockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function view(User $user, FeedStockAdjustment $adjustment): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('feed.adjust');
    }

    public function update(User $user, FeedStockAdjustment $adjustment): bool
    {
        return $user->hasPermission('feed.adjust');
    }

    public function delete(User $user, FeedStockAdjustment $adjustment): bool
    {
        return $user->hasPermission('feed.adjust');
    }
}
