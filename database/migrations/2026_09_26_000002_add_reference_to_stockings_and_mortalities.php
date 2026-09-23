<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a `reference` column to `fish_stockings` and `fish_mortalities`.
 *
 * WHY: the Pond Ledger timeline renders a Ref. column for every movement, so a
 * stocking and a mortality each need a human-readable reference (the UI shows
 * values such as "t-0119"). `pond_transfers` already carries its own reference.
 *
 * This is an INCREMENTAL, reversible change (docs/DATABASE.md §2 safety rules):
 * it only ADDs a nullable column — no table is rebuilt, no data is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fish_stockings', function (Blueprint $table) {
            $table->string('reference', 60)->nullable()->after('stocked_on')->index();
        });

        Schema::table('fish_mortalities', function (Blueprint $table) {
            $table->string('reference', 60)->nullable()->after('recorded_on')->index();
        });
    }

    public function down(): void
    {
        Schema::table('fish_stockings', function (Blueprint $table) {
            $table->dropColumn('reference');
        });

        Schema::table('fish_mortalities', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};
