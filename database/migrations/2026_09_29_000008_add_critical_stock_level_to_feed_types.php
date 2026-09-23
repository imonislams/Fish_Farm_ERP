<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a CRITICAL feed-stock level to feed_types.
 *
 * ADDITIVE and NULLABLE: every existing feed type keeps working (it simply has no
 * critical tier, so only the existing low-stock alert applies). When set, stock at
 * or below this level raises a higher-severity "critical" alert in addition to the
 * normal low-stock signal.
 *
 * This complements `low_stock_level_kg` — it does not replace it. The two tiers let
 * the farm distinguish "reorder soon" from "about to run out".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('feed_types', 'critical_stock_level_kg')) {
            return;
        }

        Schema::table('feed_types', function (Blueprint $table): void {
            $table->decimal('critical_stock_level_kg', 10, 3)
                ->nullable()
                ->after('low_stock_level_kg');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('feed_types', 'critical_stock_level_kg')) {
            return;
        }

        Schema::table('feed_types', function (Blueprint $table): void {
            $table->dropColumn('critical_stock_level_kg');
        });
    }
};
