<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Settings\UserService;
use App\Support\CompanyContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * User management (Version 1 — SINGLE COMPANY, multiple users).
 *
 * Authorization: every route carries `permission:users.*` middleware (see
 * routes/web.php). The role dropdown is populated from the roles table.
 *
 * Users always belong to THE company (docs/DATABASE.md §1) — no company_id is
 * ever accepted from the request.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /** Paginated, searchable user list. */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $roleId = $request->query('role');

        $users = User::query()
            ->with('roles:id,name,label')          // eager load: avoids N+1
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn($q) => $q->where('is_active', false))
            ->when(! empty($roleId), fn($q) => $q->whereHas(
                'roles',
                fn($r) => $r->where('roles.id', $roleId)
            ))
            ->orderBy('name')
            ->paginate(config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return view('settings.users.index', [
            'title' => 'Users',
            'users' => $users,
            'roles' => Role::orderBy('label')->get(['id', 'name', 'label']),
            'search' => $search,
            'status' => $status,
            'roleId' => $roleId,
        ]);
    }

    /** Show the create form. */
    public function create(): View
    {
        return view('settings.users.create', [
            'title' => 'Create User',
            'roles' => Role::orderBy('label')->get(['id', 'name', 'label']),
        ]);
    }

    /** Persist a new user. */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $company = CompanyContext::get();

        abort_if($company === null, 404, 'No company exists yet. Run the seeder.');

        $user = $this->userService->create($company, $request->validated());

        return redirect()
            ->route('settings.users.index')
            ->with('success', "User \"{$user->name}\" created.");
    }

    /** Show the edit form. */
    public function edit(User $user): View
    {
        $user->load('roles:id,name,label');

        return view('settings.users.edit', [
            'title' => 'Edit User',
            'user' => $user,
            'roles' => Role::orderBy('label')->get(['id', 'name', 'label']),
        ]);
    }

    /** Persist changes. */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()
            ->route('settings.users.index')
            ->with('success', "User \"{$user->name}\" updated.");
    }

    /** Activate / deactivate. */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        try {
            $updated = $this->userService->toggleActive($user, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $state = $updated->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User \"{$updated->name}\" {$state}.");
    }

    /** Delete a user. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $name = $user->name;

        try {
            $this->userService->delete($user, $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('settings.users.index')
            ->with('success', "User \"{$name}\" deleted.");
    }
}
