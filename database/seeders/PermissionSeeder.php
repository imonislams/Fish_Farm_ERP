<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Seeds the permission catalogue from config/permissions.php.
 *
 * Idempotent: uses updateOrCreate keyed on the permission name, so re-running
 * updates labels/groups rather than duplicating rows.
 *
 * Permission names are STABLE IDENTIFIERS — see docs/PERMISSIONS.md §5.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $groups = config('permissions.permissions', []);

        $count = 0;

        foreach ($groups as $group => $permissions) {
            foreach ($permissions as $name => $label) {
                Permission::updateOrCreate(
                    ['name' => $name],
                    ['group' => $group, 'label' => $label],
                );

                $count++;
            }
        }

        $this->command?->info("Permissions seeded: {$count}");
    }
}