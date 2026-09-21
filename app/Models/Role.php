<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Role — a named bundle of permissions.
 *
 * Roles are NOT tenant-scoped: Version 1 has a single company, so one flat
 * role catalogue applies. See docs/PERMISSIONS.md.
 */
class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * System roles are seeded and protected from deletion in the UI.
     * The UI must refuse to delete these; this flag is the single check.
     */
    public function isDeletable(): bool
    {
        return ! $this->is_system;
    }

    /** Grant a set of permission names, ignoring ones that do not exist. */
    public function grant(array|string $permissions): void
    {
        $ids = Permission::query()
            ->whereIn('name', (array) $permissions)
            ->pluck('id');

        $this->permissions()->syncWithoutDetaching($ids);
    }
}