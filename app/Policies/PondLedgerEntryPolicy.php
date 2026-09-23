<?php

namespace App\Policies;

use App\Models\PondLedgerEntry;
use App\Models\User;

/**
 * PondLedgerEntry policy — per-record authorization for pond ledger entries.
 *
 * Route `permission:ledger.*` middleware blocks a user without the permission
 * from reaching the page; this policy is the second layer, and is what prevents
 * access by changing an id in the URL (docs/PERMISSIONS.md §3).
 *
 * The "a generated entry cannot be deleted by hand" rule is a business rule, not
 * an authorization rule, so it lives in PondLedgerService (the write path) and
 * surfaces as a flash error rather than a 403.
 *
 * The Super Admin bypass lives solely in `Gate::before()` — never here.
 */
class PondLedgerEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ledger.view');
    }

    public function view(User $user, PondLedgerEntry $entry): bool
    {
        return $user->hasPermission('ledger.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('ledger.create');
    }

    public function update(User $user, PondLedgerEntry $entry): bool
    {
        // Entries are immutable once written: to correct one, delete it (if it is
        // a manual entry) and record the correct figure. This keeps the history
        // auditable rather than silently edited.
        return false;
    }

    public function delete(User $user, PondLedgerEntry $entry): bool
    {
        return $user->hasPermission('ledger.delete');
    }
}
