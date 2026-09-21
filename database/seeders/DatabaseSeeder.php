<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Application seeder.
 *
 * Seeds SETUP data only — the permission catalogue, the system roles, the single
 * company and the first Super Admin. It creates NO business/ERP data (no ponds,
 * feed, stock, sales or transactions), and deliberately does NOT create the
 * stock "test@example.com" user, which would be fake data.
 *
 * Order matters:
 *   permissions -> roles -> admin (which needs the super_admin role to exist)
 *
 * Idempotent — each seeder uses updateOrCreate/sync, so re-running is safe.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
