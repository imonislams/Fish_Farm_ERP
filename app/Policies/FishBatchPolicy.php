<?php

namespace App\Policies;

use App\Models\FishBatch;
use App\Models\User;

/**
 * FishBatch (stocking cycle) policy — per-record authorization.
 *
 * The batch's quantity/survival are derived by FishBatchService; this policy only
 * decides whether the user may act.
 */
class FishBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('fish.batch.view');
    }

    public function view(User $user, FishBatch $batch): bool
    {
        return $user->hasPermission('fish.batch.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('fish.batch.manage');
    }

    public function update(User $user, FishBatch $batch): bool
    {
        return $user->hasPermission('fish.batch.manage');
    }

    public function delete(User $user, FishBatch $batch): bool
    {
        return $user->hasPermission('fish.batch.manage');
    }
}
