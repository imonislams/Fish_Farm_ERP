<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fish_species — catalogue of the species the farm raises.
 *
 * Version 1 is SINGLE COMPANY: there is no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * Incremental migration — no existing table is touched.
 *
 * The species name is unique so a species cannot be entered twice; that would
 * split its stock history across two records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fish_species', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100)->unique();

            // Bengali/Bangla name and the scientific name are optional context.
            $table->string('local_name', 100)->nullable();
            $table->string('scientific_name', 150)->nullable();

            // Used as the default price when selling/harvesting; money => decimal.
            $table->decimal('default_price_per_kg', 15, 2)->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_species');
    }
};
