<?php

namespace App\Policies;

use App\Models\GrowthRecord;
use App\Models\User;

/**
 * GrowthRecord policy — per-record authorization for growth samples.
 *
 * Route `permission:` middleware blocks a user without the permission from
 * reaching the page; this policy is the second layer, and is what prevents access
 * by changing an id in the URL (docs/PERMISSIONS.md §3).
 *
 * The Super Admin bypass lives solely in `Gate::before()` — never here.
 */
class GrowthRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('growth.view');
    }

    public function view(User $user, GrowthRecord $record): bool
    {
        return $user->hasPermission('growth.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('growth.create');
    }

    public function update(User $user, GrowthRecord $record): bool
    {
        return $user->hasPermission('growth.create');
    }

    public function delete(User $user, GrowthRecord $record): bool
    {
        return $user->hasPermission('growth.create');
    }
}
