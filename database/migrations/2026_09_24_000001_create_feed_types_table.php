<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * feed_types — the feed product catalogue.
 *
 * Version 1 is SINGLE COMPANY: there is no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * `low_stock_level_kg` drives the low-feed-stock signal (FeedStockService::isLow).
 * It lives on the feed type because each product has its own sensible reorder
 * level.
 *
 * Incremental migration — no existing table is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_types', function (Blueprint $table) {
            $table->id();

            // Unique: two identical feed products would split their stock history.
            $table->string('name', 120)->unique();

            $table->string('brand', 120)->nullable();

            // Percentage of protein (0–100), stored precisely enough for labels.
            $table->decimal('protein_percent', 5, 2)->nullable();

            // Presentation unit, e.g. "kg bag". Stock itself is always in kg.
            $table->string('unit', 30)->nullable();

            // Weight of one package (kg) — used to convert bags to kg at entry.
            $table->decimal('package_weight_kg', 10, 3)->nullable();

            // Money — decimal, never float.
            $table->decimal('default_unit_cost', 15, 2)->nullable();

            // Reorder threshold in kg. Stock at or below this reads as "low".
            $table->decimal('low_stock_level_kg', 12, 3)->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_types');
    }
};
