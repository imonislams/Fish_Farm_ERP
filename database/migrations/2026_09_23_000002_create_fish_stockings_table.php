<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fish_stockings — fish put INTO a pond (stock IN).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * QUANTITY is an integer count of fish (not a weight). Weight is derived:
 *   total_weight_kg = quantity * avg_weight_g / 1000
 * and stored so reports and FCR do not have to recompute it (one calculation,
 * one place — docs/BUSINESS_LOGIC.md §10 rule 1).
 *
 * FOREIGN KEYS use restrictOnDelete: a species or pond referenced by a stocking
 * record must not vanish from under it. The pond itself additionally refuses
 * deletion while it holds live stock — see PondService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fish_stockings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('fish_species_id')
                ->constrained('fish_species')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Number of fish stocked (integer count).
            $table->unsignedInteger('quantity');

            // Average weight of one fish, in grams.
            $table->decimal('avg_weight_g', 10, 2)->nullable();

            // Derived and stored: quantity * avg_weight_g / 1000 (kg), 3 decimals.
            $table->decimal('total_weight_kg', 12, 3)->nullable();

            // Money — optional cost tracking for this stocking batch.
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();

            $table->date('stocked_on')->index();

            // The supplier is optional and the supplier module does not exist yet,
            // so this is a plain nullable column (no FK until that table lands).
            $table->string('supplier_name', 150)->nullable();

            $table->text('note')->nullable();

            // Attribution — who recorded it. Nulled if the user is later removed.
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Common list filter: stockings for a pond, newest first.
            $table->index(['pond_id', 'stocked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_stockings');
    }
};
