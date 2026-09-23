<?php

namespace App\Policies;

use App\Models\FeedType;
use App\Models\User;

/**
 * FeedType policy — per-record authorization for the feed product catalogue.
 *
 * Same two-layer model as the pond and fish modules: route `permission:`
 * middleware plus this per-record check. The "type in use cannot be deleted"
 * rule is a business rule, not an authorization rule, so it lives in
 * FeedTypeService (the write path) and surfaces as a flash error, not a 403.
 */
class FeedTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function view(User $user, FeedType $type): bool
    {
        return $user->hasPermission('feed.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('feed.type.manage');
    }

    public function update(User $user, FeedType $type): bool
    {
        return $user->hasPermission('feed.type.manage');
    }

    public function delete(User $user, FeedType $type): bool
    {
        return $user->hasPermission('feed.type.manage');
    }
}
