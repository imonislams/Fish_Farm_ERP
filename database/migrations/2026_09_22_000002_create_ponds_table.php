<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ponds — the primary operating unit of the farm.
 *
 * Version 1 is SINGLE COMPANY: there is no company_id/farm_id column. The pond
 * references its domain parent — the pond type — and nothing else. See
 * docs/DATABASE.md §1 and §5.
 *
 * MEASUREMENTS use DECIMAL, never float: `size` and `depth` are exact quantities
 * that are later summed and compared (feed per area, stocking density, reports).
 * Binary floating point would introduce rounding drift in those figures.
 *
 * FOREIGN KEY: a pond type that is still referenced by ponds cannot be deleted —
 * `restrictOnDelete()` enforces this at the database level, and PondTypeService
 * turns the constraint into a clear application error. Pond types are edited, not
 * removed, so `cascadeOnUpdate()` is safe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ponds', function (Blueprint $table) {
            $table->id();

            // Unique operating identifier, e.g. "P-01".
            $table->string('pond_number', 50)->unique();

            $table->string('name', 150);

            // Required: a pond without a classification would be meaningless.
            $table->foreignId('pond_type_id')
                ->constrained('pond_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Measurements — decimal, never float.
            $table->decimal('size', 12, 3);
            $table->string('size_unit', 20);
            $table->decimal('depth', 10, 3)->nullable();
            $table->string('depth_unit', 20)->nullable();

            $table->string('location', 255)->nullable();
            $table->string('water_source', 100)->nullable();

            // Canonical values live in config/ponds.php (see PondStatus).
            $table->string('status', 30)->default('active')->index();

            $table->text('description')->nullable();

            // Mirrors the status in a boolean for simple "is this pond usable?" checks.
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            // Composite index for the common "status within a type" list filter.
            $table->index(['pond_type_id', 'status'], 'ponds_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ponds');
    }
};
