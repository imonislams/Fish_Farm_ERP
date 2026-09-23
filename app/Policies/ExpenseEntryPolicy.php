<?php

namespace App\Policies;

use App\Models\ExpenseEntry;
use App\Models\User;

/**
 * ExpenseEntry policy — per-record authorization.
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
class ExpenseEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('finance.view');
    }

    public function view(User $user, ExpenseEntry $model): bool
    {
        return $user->hasPermission('finance.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('expense.create');
    }

    public function update(User $user, ExpenseEntry $model): bool
    {
        return $user->hasPermission('expense.update');
    }

    public function delete(User $user, ExpenseEntry $model): bool
    {
        return $user->hasPermission('expense.delete');
    }
}
