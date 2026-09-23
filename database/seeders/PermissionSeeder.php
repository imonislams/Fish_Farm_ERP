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

        $catalogue = [];

        foreach ($groups as $group => $permissions) {
            foreach ($permissions as $name => $label) {
                Permission::updateOrCreate(
                    ['name' => $name],
                    ['group' => $group, 'label' => $label],
                );

                $catalogue[] = $name;
            }
        }

        $this->command?->info('Permissions seeded: '.count($catalogue));

        // Retire permissions that no longer exist in the catalogue, so a removed
        // key cannot linger in the database and remain silently grantable.
        // Pivot rows are removed by the cascade on `permission_role`.
        $removed = Permission::query()
            ->whereNotIn('name', $catalogue)
            ->pluck('name');

        if ($removed->isNotEmpty()) {
            Permission::query()->whereIn('name', $removed->all())->delete();

            $this->command?->warn('Retired permissions removed: '.$removed->implode(', '));
        }
    }
}