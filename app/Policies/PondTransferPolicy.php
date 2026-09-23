<?php

namespace App\Policies;

use App\Models\PondTransfer;
use App\Models\User;

/**
 * PondTransfer policy — per-record authorization for pond-to-pond transfers.
 *
 * The transfer business rules (source ≠ destination, positive quantity, source
 * must cover the quantity, atomicity) live in FishStockService — the write path.
 * This policy only decides whether the user may act.
 *
 * Registered in AppServiceProvider. The Super Admin bypass lives solely in
 * `Gate::before()` — never here.
 */
class PondTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pond_ledger.view');
    }

    public function view(User $user, PondTransfer $transfer): bool
    {
        return $user->hasPermission('pond_ledger.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pond_ledger.transfer.create');
    }

    public function update(User $user, PondTransfer $transfer): bool
    {
        // A transfer is immutable once written: to correct one, delete it and
        // record the correct movement. This keeps the history auditable.
        return false;
    }

    public function delete(User $user, PondTransfer $transfer): bool
    {
        return $user->hasPermission('pond_ledger.transfer.delete');
    }
}
