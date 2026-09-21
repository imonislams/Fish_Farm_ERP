<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Contracts\View\View;

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
    /** Grouped permission catalogue. */
    public function index(): View
    {
        $groups = Permission::query()
            ->with('roles:id,label')            // eager load: avoids N+1
            ->orderBy('group')
            ->orderBy('name')
            ->get(['id', 'name', 'group', 'label'])
            ->groupBy('group');

        return view('settings.permissions.index', [
            'title' => 'Permissions',
            'groups' => $groups,
            'total' => Permission::count(),
        ]);
    }
}
