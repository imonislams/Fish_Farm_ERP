<?php

namespace App\Policies;

use App\Models\PartyTransaction;
use App\Models\User;

/**
 * PartyTransaction policy — per-record authorization.
 *
 * Route `permission:` middleware blocks a user without the permission from
 * reaching the page; this policy is the second layer, and is what prevents access
 * by changing an id in the URL (docs/PERMISSIONS.md §3).
 *
 * Business rules (e.g. "a customer with sales cannot be deleted") live in the
 * service layer — the write path — and surface as a flash/toast error, not a 403.
 *
 * The Super Admin bypass lives solely in `Gate::before()` — never here.
 */
class PartyTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('party.view');
    }

    public function view(User $user, PartyTransaction $model): bool
    {
        return $user->hasPermission('party.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('party.transaction.create');
    }

    public function update(User $user, PartyTransaction $model): bool
    {
        return $user->hasPermission('party.transaction.create');
    }

    public function delete(User $user, PartyTransaction $model): bool
    {
        return $user->hasPermission('party.transaction.create');
    }
}
