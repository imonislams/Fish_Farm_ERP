<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * User — belongs to THE company, and holds roles/permissions.
 *
 * Relationship chain (Version 1, single company):
 *   Company -> Users -> Roles & Permissions
 *
 * Authorization is not tenant-based: there is one company, so a user's access
 * is decided purely by their permissions. See docs/PERMISSIONS.md.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /* ---------------------------------------------------------------------
     | Relationships
     |---------------------------------------------------------------------*/

    /** The single company this user belongs to. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Roles granted to the user. */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /* ---------------------------------------------------------------------
     | Authorization helpers
     |---------------------------------------------------------------------*/

    /**
     * All permission names the user holds, via their roles.
     * Cached per request instance so repeated checks do not re-query.
     */
    public function permissionNames(): Collection
    {
        return once(fn (): Collection => $this->roles
            ->flatMap(fn (Role $role): Collection => $role->permissions->pluck('name'))
            ->unique()
            ->values());
    }

    /**
     * Does the user hold this permission?
     *
     * Super Admin is a role name check — the ONLY role shortcut in the system.
     * Every other decision goes through a real permission key (docs/PERMISSIONS.md).
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        return $this->permissionNames()->contains($permission);
    }

    /** Does the user hold this role (by machine name)? */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /** Does the user hold any of the given roles? */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    /* ---------------------------------------------------------------------
     | Access state
     |---------------------------------------------------------------------*/

    /**
     * Inactive users must not authenticate. Enforced in the login flow
     * (authentication module phase) — see docs/PERMISSIONS.md §7.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
