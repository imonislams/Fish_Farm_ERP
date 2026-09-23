<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Settings\UserService;
use App\Support\CompanyContext;
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

    /** Paginated, searchable user list (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $roleId = $request->query('role');
        $currentId = auth()->id();

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

        return \Inertia\Inertia::render('Settings/Users/Index', [
            'title' => 'Users',
            'users' => [
                'data' => collect($users->items())->map(fn (User $u): array => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->roles->first()?->label,
                    'is_active' => (bool) $u->is_active,
                    'is_self' => $u->id === $currentId,
                    'created_at' => $u->created_at?->format('d M Y'),
                    'urls' => [
                        'edit' => route('settings.users.edit', $u, absolute: false),
                        'toggle' => route('settings.users.toggle-active', $u, absolute: false),
                        'destroy' => route('settings.users.destroy', $u, absolute: false),
                    ],
                ])->all(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'links' => $users->linkCollection()->toArray(),
            ],
            'roles' => Role::orderBy('label')->pluck('label', 'id')->all(),
            'filters' => ['search' => $search, 'status' => $status, 'role' => $roleId],
            'currentUserId' => $currentId,
        ]);
    }

    /** Show the create form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Settings/Users/Create', [
            'title' => 'Create User',
            'roles' => Role::orderBy('label')->pluck('label', 'id')->all(),
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

    /** Show the edit form (Inertia/React). */
    public function edit(User $user): \Inertia\Response
    {
        $user->load('roles:id,name,label');

        return \Inertia\Inertia::render('Settings/Users/Edit', [
            'title' => 'Edit User',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->roles->first()?->id,
                'is_active' => (bool) $user->is_active,
                'is_self' => $user->id === auth()->id(),
            ],
            'roles' => Role::orderBy('label')->pluck('label', 'id')->all(),
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
