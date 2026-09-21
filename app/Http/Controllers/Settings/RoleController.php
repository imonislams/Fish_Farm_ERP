<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRoleRequest;
use App\Http\Requests\Settings\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Settings\RoleService;
use Illuminate\Contracts\View\View;
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

    /** Role list with user/permission counts. */
    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])   // avoids N+1
            ->orderByDesc('is_system')
            ->orderBy('label')
            ->get();

        return view('settings.roles.index', [
            'title' => 'Roles',
            'roles' => $roles,
        ]);
    }

    /** Show the create form with the grouped permission matrix. */
    public function create(): View
    {
        return view('settings.roles.create', [
            'title' => 'Create Role',
            'groups' => $this->permissionGroups(),
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

    /** Show the edit form (details + permission matrix). */
    public function edit(Role $role): View
    {
        $role->load('permissions:id,name');

        return view('settings.roles.edit', [
            'title' => 'Edit Role',
            'role' => $role,
            'groups' => $this->permissionGroups(),
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
                'permissions' => $permissions,
            ])
            ->values();
    }
}
