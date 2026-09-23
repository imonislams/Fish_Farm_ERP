<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * purchase_items — purchased line items (feed, fingerlings, equipment, other).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `item_type` decides which of the two optional FKs is meaningful:
 *   feed        → feed_type_id
 *   fingerlings → fish_species_id
 *   equipment / other → neither (description only)
 *
 * `line_total` is DERIVED (quantity × unit_cost) by PurchaseService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnDelete();

            // feed | fingerlings | equipment | other  (key from config/finance.php)
            $table->string('item_type', 30)->default('other')->index();

            $table->foreignId('feed_type_id')->nullable()
                ->constrained('feed_types')->nullOnDelete();
            $table->foreignId('fish_species_id')->nullable()
                ->constrained('fish_species')->nullOnDelete();

            $table->string('description', 255)->nullable();

            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);

            // Derived and stored.
            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
