<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Permission;

/**
 * Permissions reference (read-only list).
 *
 * Permissions are defined in config/permissions.php and seeded — they are not
 * created through the UI, because a permission only means something once code
 * enforces it. This page documents what exists and which roles hold each key.
 *
 * Assigning a permission to a role happens on the Role pages.
 * See docs/PERMISSIONS.md §8.
 */
class PermissionController extends Controller
{
    /** Grouped permission catalogue (Inertia/React). */
    public function index(): \Inertia\Response
    {
        $groups = Permission::query()
            ->with('roles:id,label')            // eager load: avoids N+1
            ->orderBy('group')
            ->orderBy('name')
            ->get(['id', 'name', 'group', 'label'])
            ->groupBy('group')
            ->map(fn ($permissions, $group): array => [
                'group' => $group,
                'permissions' => $permissions->map(fn (Permission $p): array => [
                    'name' => $p->name,
                    'label' => $p->label,
                    'roles' => $p->roles->pluck('label')->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return \Inertia\Inertia::render('Settings/Permissions/Index', [
            'title' => 'Permissions',
            'groups' => $groups,
            'total' => Permission::count(),
        ]);
    }
}
