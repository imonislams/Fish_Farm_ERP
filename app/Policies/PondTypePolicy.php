<?php

namespace App\Policies;

use App\Models\PondType;
use App\Models\User;

/**
 * PondType policy — per-record authorization for the master-data catalogue.
 *
 * Same two-layer model as PondPolicy: route `permission:` middleware plus this
 * per-record check. The "type in use cannot be deleted" rule is a business rule,
 * not an authorization rule, so it lives in PondTypeService (the write path) and
 * is surfaced as a flash error rather than a 403.
 */
class PondTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pond_type.view');
    }

    public function view(User $user, PondType $type): bool
    {
        return $user->hasPermission('pond_type.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('pond_type.create');
    }

    public function update(User $user, PondType $type): bool
    {
        return $user->hasPermission('pond_type.update');
    }

    public function delete(User $user, PondType $type): bool
    {
        return $user->hasPermission('pond_type.delete');
    }
}
