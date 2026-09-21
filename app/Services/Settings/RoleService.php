<?php

namespace App\Services\Settings;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Role management business logic.
 *
 * Creating/updating a role AND its permission grants happens inside one
 * transaction, so a role is never left half-configured.
 *
 * Permission names are validated by the FormRequest before they reach here;
 * this service resolves them to ids so an unknown name can never be stored.
 * See docs/PERMISSIONS.md.
 */
class RoleService
{
    /**
     * Create a role and grant its permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::create([
                'name' => $data['name'],
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'is_system' => false, // user-created roles are always deletable
            ]);

            $this->syncPermissions($role, $data['permissions'] ?? []);

            return $role;
        });
    }

    /**
     * Update a role and its permission grants.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->label = $data['label'];
            $role->description = $data['description'] ?? null;

            // System roles keep their machine name (guarded in the request too).
            if (! $role->is_system) {
                $role->name = $data['name'];
            }

            $role->save();

            $this->syncPermissions($role, $data['permissions'] ?? []);

            return $role->refresh();
        });
    }

    /**
     * Delete a role.
     *
     * Refuses to delete a system role, or any role still assigned to users —
     * that would leave those users with access. Enforced here because this
     * is the write path (never trust the UI to prevent it).
     */
    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw new \DomainException('System roles cannot be deleted.');
        }

        $assigned = $role->users()->count();

        if ($assigned > 0) {
            throw new \DomainException(
                "This role is assigned to {$assigned} user(s). Reassign them before deleting it."
            );
        }

        DB::transaction(function () use ($role): void {
            $role->permissions()->detach();
            $role->delete();
        });
    }

    /**
     * Replace the role's permissions with the given names.
     *
     * @param  array<int, string>  $names
     */
    private function syncPermissions(Role $role, array $names): void
    {
        $ids = Permission::query()
            ->whereIn('name', array_unique($names))
            ->pluck('id')
            ->all();

        $role->permissions()->sync($ids);
    }
}
