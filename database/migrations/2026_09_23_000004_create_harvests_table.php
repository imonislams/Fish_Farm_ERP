<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * harvests — fish removed from a pond (stock OUT).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * QUANTITY is an integer count of fish; `total_weight_kg` is the weighed total
 * (kg) and `avg_weight_g` the average weight of one fish. A harvest can never
 * take a pond's stock below zero — the service rejects it before writing
 * (docs/BUSINESS_LOGIC.md §2).
 *
 * A harvest will later feed sale availability; that link belongs to the Sales
 * module and is not created here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('fish_species_id')
                ->constrained('fish_species')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedInteger('quantity');

            $table->decimal('total_weight_kg', 12, 3)->nullable();
            $table->decimal('avg_weight_g', 10, 2)->nullable();

            $table->date('harvested_on')->index();
            $table->string('destination', 150)->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['pond_id', 'harvested_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
