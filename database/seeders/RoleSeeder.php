<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeds the system roles and their permission grants from
 * config/permissions.php.
 *
 * Idempotent:
 *   - roles are updateOrCreate'd on their stable machine name
 *   - permissions are synced (not attached blindly), so removing a permission
 *     from the config and re-seeding actually revokes it
 *
 * `super_admin` is granted the wildcard '*'. It is the ONLY role shortcut in the
 * codebase (User::hasPermission() returns true for it unconditionally), so it
 * does not need every individual permission attached. See docs/PERMISSIONS.md §7.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = config('permissions.roles', []);
        $allPermissionIds = Permission::query()->pluck('id', 'name');

        foreach ($roles as $name => $definition) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $definition['label'],
                    'description' => $definition['description'] ?? null,
                    'is_system' => $definition['is_system'] ?? true,
                ],
            );

            $granted = $definition['permissions'] ?? [];

            // Wildcard: grant every permission. Keeps the catalogue honest
            // (Super Admin genuinely holds them all) without hand-listing.
            if (in_array('*', $granted, true)) {
                $role->permissions()->sync($allPermissionIds->values()->all());

                continue;
            }

            $ids = collect($granted)
                ->map(fn (string $permission): ?int => $allPermissionIds->get($permission))
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($ids);
        }

        $this->command?->info('Roles seeded: '.count($roles));
    }
}