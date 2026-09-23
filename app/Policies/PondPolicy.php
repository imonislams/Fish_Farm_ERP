<?php

namespace App\Policies;

use App\Models\Pond;
use App\Models\User;

/**
 * Pond policy — per-record authorization.
 *
 * Route `permission:` middleware already blocks a user without the permission
 * from reaching the page. The policy is the second layer: it answers "may THIS
 * user act on THIS pond?" and is what actually prevents access by changing an id
 * in the URL (docs/PERMISSIONS.md §3). It also gives the module a place to grow
 * record-specific rules later (e.g. a pond holding live stock cannot be deleted).
 *
 * The Super Admin bypass lives solely in `Gate::before()` — never here.
 */
class PondPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pond.view');
    }

    public function view(User $user, Pond $pond): bool
    {
        return $user->hasPermission('pond.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pond.create');
    }

    public function update(User $user, Pond $pond): bool
    {
        return $user->hasPermission('pond.update');
    }

    public function delete(User $user, Pond $pond): bool
    {
        // Version 1 is single-company, so there is no ownership dimension to
        // check — the permission is the whole rule. When fish stock lands, a
        // "pond with live stock cannot be deleted" guard belongs here AND in
        // PondService (the write path).
        return $user->hasPermission('pond.delete');
    }
}
