<?php

namespace App\Policies;

use App\Models\FeedUsage;
use App\Models\User;

/**
 * FeedUsage policy — per-record authorization for stock-OUT usages.
 *
 * The non-negative stock guard is a business rule and lives in FeedStockService
 * (the write path); this policy only decides whether the user may act.
 */
class FeedUsagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function view(User $user, FeedUsage $usage): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('feed.usage');
    }

    public function update(User $user, FeedUsage $usage): bool
    {
        return $user->hasPermission('feed.usage');
    }

    public function delete(User $user, FeedUsage $usage): bool
    {
        return $user->hasPermission('feed.usage');
    }
}
