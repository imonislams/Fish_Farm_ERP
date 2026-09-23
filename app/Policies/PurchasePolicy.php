<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

/**
 * Purchase policy — per-record authorization.
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
class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('supplier.view');
    }

    public function view(User $user, Purchase $model): bool
    {
        return $user->hasPermission('supplier.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('supplier.purchase.create');
    }

    public function update(User $user, Purchase $model): bool
    {
        return $user->hasPermission('supplier.purchase.create');
    }

    public function delete(User $user, Purchase $model): bool
    {
        return $user->hasPermission('supplier.purchase.create');
    }
}
