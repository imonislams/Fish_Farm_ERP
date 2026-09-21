<?php

namespace App\Services\Settings;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * User management business logic.
 *
 * The controller stays thin and this service owns the writes: creating/updating
 * a user AND their role assignment happen inside one transaction so the database
 * is never left with a user who has no role. See docs/ARCHITECTURE.md.
 *
 * Version 1 is SINGLE-COMPANY: every user belongs to the one company. The
 * company is passed in explicitly and is NEVER taken from user input.
 */
class UserService
{
    /**
     * Create a user and assign their role.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Company $company, array $data): User
    {
        return DB::transaction(function () use ($company, $data): User {
            $user = User::create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'email_verified_at' => now(),
            ]);

            $this->syncRole($user, (int) $data['role_id']);

            return $user;
        });
    }

    /**
     * Update a user and re-assign their role.
     *
     * The password is only changed when a new one is supplied (blank = keep).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->is_active = (bool) ($data['is_active'] ?? false);

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            $this->syncRole($user, (int) $data['role_id']);

            return $user->refresh();
        });
    }

    /**
     * Toggle the active state.
     *
     * A user must never deactivate themselves (guarded in the request and again
     * here, since this is the write path).
     */
    public function toggleActive(User $user, User $actor): User
    {
        if ($user->id === $actor->id) {
            throw new \DomainException('You cannot change your own account status.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return $user;
    }

    /**
     * Delete a user.
     *
     * Refuses to delete the last active Super Admin so the system can never be
     * locked out of its own administration.
     */
    public function delete(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw new \DomainException('You cannot delete your own account.');
        }

        if ($user->hasRole('super_admin') && $this->activeSuperAdminCount() <= 1) {
            throw new \DomainException('You cannot delete the last active Super Admin.');
        }

        DB::transaction(function () use ($user): void {
            // Detach roles first so no orphan pivot rows remain.
            $user->roles()->detach();
            $user->delete();
        });
    }

    /**
     * Version 1: a user holds ONE primary role.
     * `sync()` replaces any previous assignment, keeping the model simple.
     */
    private function syncRole(User $user, int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        $user->roles()->sync([$role->id]);
    }

    private function activeSuperAdminCount(): int
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->where('name', 'super_admin'))
            ->count();
    }
}
