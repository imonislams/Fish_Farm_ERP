<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sale_items — the line items of a sale.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `line_total` is DERIVED (weight_kg × unit_price, or quantity × unit_price when
 * the line is sold by count) by SalesService — never entered twice.
 *
 * The FK cascades: deleting a sale removes its lines (the service decides whether
 * a delete is allowed at all; the cascade only guarantees no orphans).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();

            // Optional: a sale line may name the species and the pond it came from.
            $table->foreignId('fish_species_id')->nullable()
                ->constrained('fish_species')->nullOnDelete();
            $table->foreignId('pond_id')->nullable()
                ->constrained('ponds')->nullOnDelete();

            // Count of fish (optional — a sale may be sold purely by weight).
            $table->unsignedInteger('quantity')->nullable();

            // Weight sold, in kg.
            $table->decimal('weight_kg', 12, 3)->nullable();

            $table->decimal('unit_price', 15, 2)->default(0);

            // Derived and stored: the line total in money.
            $table->decimal('line_total', 15, 2)->default(0);

            $table->string('description', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
