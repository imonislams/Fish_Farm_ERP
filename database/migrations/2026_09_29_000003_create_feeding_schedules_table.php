<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * feeding_schedules — the planned feeding of a pond (a meal schedule).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A schedule is a PLAN only. It does NOT move feed stock. Feed stock is reduced
 * only when a feeding is actually PERFORMED and recorded in `feedings`
 * (docs/BUSINESS_LOGIC.md §2) — creating a schedule must never consume inventory.
 *
 * `recurrence` is 'once' (a dated plan) or 'daily' (repeats at this time each day);
 * daily keeps the model simple while covering the common farm routine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeding_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('feed_type_id')
                ->constrained('feed_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // The planned meal: date + time + planned quantity.
            $table->date('scheduled_on')->index();
            $table->time('scheduled_time');
            $table->decimal('planned_quantity_kg', 10, 3);

            // once | daily  (config key `feed_schedule_recurrences`).
            $table->string('recurrence', 20)->default('once');
            $table->boolean('is_active')->default(true)->index();
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['pond_id', 'scheduled_on']);
            $table->index(['scheduled_on', 'scheduled_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_schedules');
    }
};
