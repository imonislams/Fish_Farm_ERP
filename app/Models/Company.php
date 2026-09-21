<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Company — the SINGLE business identity (Version 1).
 *
 * Architecture decision (docs/DATABASE.md §1): this application is
 * single-company. Exactly one row is expected and the company is NOT repeated
 * as a foreign key on business tables.
 *
 * There is no tenant middleware, no tenant switching and no company selector.
 * Do not build those. See the migration for the corresponding database note.
 */
class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'code',
        'logo',
        'phone',
        'email',
        'address',
        'currency',
        'timezone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * Users belonging to the company.
     *
     * Company -> Users -> Roles & Permissions is the whole relationship chain in
     * Version 1.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * The current company.
     *
     * There is only ever one. This helper exists so the Settings module and the
     * admin UI do not each invent their own lookup, and so a future
     * multi-company version has exactly one place to change.
     */
    public static function current(): ?self
    {
        return static::query()->orderBy('id')->first();
    }
}