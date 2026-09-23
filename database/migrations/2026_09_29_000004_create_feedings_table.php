<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * feedings — an ACTUAL feeding performed (the real consumption record).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * Recording a feeding writes a `feed_usages` row through FeedStockService, so it
 * DECREASES feed stock and contributes to FCR — this table adds the meal context
 * (when, and against which schedule) on top of the stock movement. It never
 * maintains its own stock figure; FeedStockService remains the single source.
 *
 * `feeding_schedule_id` is nullable: an ad-hoc feeding need not follow a plan.
 * `feed_usage_id` links to the stock movement so deleting the feeding can restore
 * the stock it consumed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('feed_type_id')
                ->constrained('feed_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // The plan this feeding fulfils, when it followed one.
            $table->foreignId('feeding_schedule_id')->nullable()
                ->constrained('feeding_schedules')->nullOnDelete();

            // The stock movement this feeding produced (feed_usages row).
            $table->foreignId('feed_usage_id')->nullable()
                ->constrained('feed_usages')->nullOnDelete();

            $table->decimal('planned_quantity_kg', 10, 3)->nullable();
            $table->decimal('consumed_quantity_kg', 10, 3);

            $table->date('fed_on')->index();
            $table->time('fed_at')->nullable();
            // completed | partial | skipped  (config key `feeding_statuses`).
            $table->string('status', 20)->default('completed')->index();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['pond_id', 'fed_on']);
            $table->index(['feeding_schedule_id', 'fed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedings');
    }
};
