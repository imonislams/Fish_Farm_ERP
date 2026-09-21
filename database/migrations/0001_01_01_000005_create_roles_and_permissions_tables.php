<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles and permissions (single-company authorization).
 *
 * Relationship: Company -> Users -> Roles & Permissions.
 * Roles are NOT per-company and are NOT tenant-scoped: Version 1 has one
 * company, so a single flat role/permission catalogue applies to it.
 *
 * See docs/PERMISSIONS.md for the permission catalogue and role mapping.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();   // machine key, e.g. farm_admin
            $table->string('label');                 // human label, e.g. "Farm Admin"
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false); // protects seeded roles
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();   // resource.action
            $table->string('group', 100)->index();   // module name for grouped UI
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        // Drop pivots first so foreign keys resolve cleanly.
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};