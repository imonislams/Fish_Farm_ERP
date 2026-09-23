<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * inspection_schedules — the recurring inspection plan for a pond.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `next_due_on` drives the "inspection due" and "overdue" signals. It is DERIVED
 * by InspectionScheduleService from a completion date and the frequency interval —
 * never typed in — so it can never disagree with the frequency that produced it.
 *
 * One schedule per pond (a unique index enforces it): a pond inspected on two
 * different rhythms would make "is it due?" ambiguous.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->unique()
                ->constrained('ponds')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // Interval key from config/fcr.php (daily, weekly, monthly, …).
            $table->string('frequency', 30);

            // Derived: last completion (or creation) + frequency interval.
            $table->date('next_due_on')->index();

            $table->date('last_completed_on')->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_schedules');
    }
};
