<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

/**
 * Inspection policy — per-record authorization for pond inspections.
 *
 * Route `permission:` middleware blocks a user without the permission from
 * reaching the page; this policy is the second layer, and is what prevents access
 * by changing an id in the URL (docs/PERMISSIONS.md §3).
 */
class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fcr.view');
    }

    public function view(User $user, Inspection $inspection): bool
    {
        return $user->hasPermission('fcr.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fcr.inspection.create');
    }

    public function update(User $user, Inspection $inspection): bool
    {
        return $user->hasPermission('fcr.inspection.update');
    }

    public function delete(User $user, Inspection $inspection): bool
    {
        return $user->hasPermission('fcr.inspection.update');
    }
}
