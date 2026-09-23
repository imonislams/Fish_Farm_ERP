<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * inspections — a pond inspection and its findings.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * All measurement columns are NULLABLE on purpose: an inspection may cover only
 * some parameters, and storing 0 for "not measured" would be a lie that pollutes
 * every later average. A missing reading stays null and renders as "—".
 *
 * `health_status` is required — an inspection always reaches a conclusion, even
 * if that conclusion is "monitor". Values come from config/fcr.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->date('inspected_on')->index();

            // Who carried out the inspection (free text — not necessarily a user).
            $table->string('inspected_by', 150)->nullable();

            // Water-quality readings. `parameterNames` in config/fcr.php lists the
            // canonical set; each is optional.
            $table->decimal('water_ph', 5, 2)->nullable();
            $table->decimal('water_temp_c', 5, 2)->nullable();
            $table->decimal('dissolved_oxygen', 6, 2)->nullable();
            $table->decimal('ammonia', 6, 3)->nullable();
            $table->decimal('turbidity', 8, 2)->nullable();

            // Required conclusion. Keys from config/fcr.php.
            $table->string('health_status', 20)->default('healthy')->index();

            $table->text('action_taken')->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Common list filter: one pond's inspections, newest first.
            $table->index(['pond_id', 'inspected_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
