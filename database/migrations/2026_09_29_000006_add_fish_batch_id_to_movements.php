<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `fish_batch_id` to the existing fish movement tables.
 *
 * ADDITIVE and NULLABLE: every existing row keeps working (its batch is simply
 * unassigned). This is what lets a batch report its own quantity/survival WITHOUT
 * a second stock system — the movements stay the single source of truth and the
 * batch is just a tag over them (docs/BUSINESS_LOGIC.md §2).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['fish_stockings', 'fish_mortalities', 'harvests'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreignId('fish_batch_id')->nullable()
                    ->after('pond_id')
                    ->constrained('fish_batches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['fish_stockings', 'fish_mortalities', 'harvests'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropForeign(['fish_batch_id']);
                $blueprint->dropColumn('fish_batch_id');
            });
        }
    }
};
