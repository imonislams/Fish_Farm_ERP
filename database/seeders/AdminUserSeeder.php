<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Bootstraps the single company and the FIRST system administrator.
 *
 * This is setup data, not business data:
 *   - one `companies` row (the single business identity for Version 1)
 *   - one Super Admin user, so someone can sign in and manage the company,
 *     users, roles and permissions
 *
 * It creates NO ERP data — no ponds, feed, stock, sales or transactions.
 *
 * Idempotent: safe to re-run. Existing rows are updated, never duplicated.
 *
 * Credentials come from .env (ADMIN_EMAIL / ADMIN_PASSWORD) so no password is
 * hard-coded in the repository. If ADMIN_PASSWORD is unset a random one is
 * generated and printed once.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // --- The single company ------------------------------------------
        $company = Company::query()->orderBy('id')->first();

        if (! $company) {
            $company = Company::create([
                'name' => config('fishfarm.company.name'),
                'code' => config('fishfarm.company.code'),
                'phone' => config('fishfarm.company.phone'),
                'email' => config('fishfarm.company.email'),
                'address' => config('fishfarm.company.address'),
                'currency' => config('fishfarm.currency_code', 'BDT'),
                'timezone' => config('app.timezone', 'Asia/Dhaka'),
                'status' => Company::STATUS_ACTIVE,
            ]);

            $this->command?->info("Company created: {$company->name}");
        } else {
            $this->command?->info("Company already exists: {$company->name} (left unchanged)");
        }

        // --- The first Super Admin ---------------------------------------
        $email = config('fishfarm.admin.email');
        $password = config('fishfarm.admin.password');

        if (empty($password)) {
            $password = \Illuminate\Support\Str::password(16);
            $this->command?->warn("No ADMIN_PASSWORD set. Generated password: {$password}");
        }

        $superAdmin = Role::where('name', 'super_admin')->first();

        if (! $superAdmin) {
            $this->command?->error('super_admin role missing — run RoleSeeder first.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'company_id' => $company->id,
                'name' => config('fishfarm.admin.name'),
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        // syncWithoutDetaching keeps any other roles already granted.
        $admin->roles()->syncWithoutDetaching([$superAdmin->id]);

        $this->command?->info("Super Admin ready: {$admin->email}");
    }
}