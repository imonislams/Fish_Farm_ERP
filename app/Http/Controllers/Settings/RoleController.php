<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRoleRequest;
use App\Http\Requests\Settings\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Settings\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * Role management (Version 1 — single company).
 *
 * Authorization: every route carries `permission:roles.*` middleware; the
 * permission matrix additionally requires `permissions.manage` (see
 * routes/web.php). System roles are protected from deletion in the service.
 *
 * The permission catalogue is grouped for display so the matrix is readable
 * rather than one long list — see docs/UI_GUIDELINES.md.
 */
class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    /** Role list with user/permission counts (Inertia/React). */
    public function index(): \Inertia\Response
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])   // avoids N+1
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
                'description' => $role->description,
                'is_system' => (bool) $role->is_system,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'urls' => [
                    'edit' => route('settings.roles.edit', $role, absolute: false),
                    'destroy' => route('settings.roles.destroy', $role, absolute: false),
                ],
            ])
            ->all();

        return \Inertia\Inertia::render('Settings/Roles/Index', [
            'title' => 'Roles',
            'roles' => $roles,
        ]);
    }

    /** Show the create form with the grouped permission matrix (Inertia/React). */
    public function create(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Settings/Roles/Create', [
            'title' => 'Create Role',
            'groups' => $this->permissionGroups()->all(),
            'selected' => old('permissions', []),
        ]);
    }

    /** Persist a new role. */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create($request->validated());

        return redirect()
            ->route('settings.roles.index')
            ->with('success', "Role \"{$role->label}\" created.");
    }

    /** Show the edit form (details + permission matrix) — Inertia/React. */
    public function edit(Role $role): \Inertia\Response
    {
        $role->load('permissions:id,name');

        return \Inertia\Inertia::render('Settings/Roles/Edit', [
            'title' => 'Edit Role',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label,
                'description' => $role->description,
                'is_system' => (bool) $role->is_system,
            ],
            'groups' => $this->permissionGroups()->all(),
            'selected' => old('permissions', $role->permissions->pluck('name')->all()),
        ]);
    }

    /** Persist changes. */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated());

        return redirect()
            ->route('settings.roles.index')
            ->with('success', "Role \"{$role->label}\" updated.");
    }

    /** Delete a role. */
    public function destroy(Role $role): RedirectResponse
    {
        $label = $role->label;

        try {
            $this->roleService->delete($role);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('settings.roles.index')
            ->with('success', "Role \"{$label}\" deleted.");
    }

    /**
     * The permission catalogue grouped by module, for the matrix UI.
     *
     * Derived from the database (not the config file) so the matrix always
     * reflects what is actually grantable. Each group is
     * ['group' => string, 'permissions' => Collection].
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function permissionGroups(): Collection
    {
        return Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get(['id', 'name', 'group', 'label'])
            ->groupBy('group')
            ->map(fn($permissions, $group): array => [
                'group' => $group,
                'permissions' => $permissions->map(fn ($p): array => [
                    'name' => $p->name,
                    'label' => $p->label,
                ])->values()->all(),
            ])
            ->values();
    }
}
