<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pond_transfers — fish moved FROM one pond TO another (stock OUT of source,
 * stock IN to destination).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A transfer is a single atomic movement that touches TWO ponds, so it is stored
 * as ONE row with both a source and a destination — never as two unrelated rows
 * that could drift apart (docs/BUSINESS_LOGIC.md §2a):
 *
 *   source pond      → stock DECREASE (transfer_out)
 *   destination pond → stock INCREASE (transfer_in)
 *
 * QUANTITY is an integer count of fish. The row is written by FishStockService
 * inside DB::transaction(); if either side fails the whole transfer rolls back.
 *
 * FOREIGN KEYS use restrictOnDelete: a pond referenced by a transfer must not
 * vanish from under it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pond_transfers', function (Blueprint $table) {
            $table->id();

            // Source pond — stock leaves here.
            $table->foreignId('from_pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Destination pond — stock arrives here.
            $table->foreignId('to_pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Species being moved (optional: a transfer may be recorded as a bare
            // count when the pond holds a single species).
            $table->foreignId('fish_species_id')
                ->nullable()
                ->constrained('fish_species')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Number of fish moved (integer count).
            $table->unsignedInteger('quantity');

            $table->date('transferred_on')->index();

            // Human-readable reference for the movement (auto-generated).
            $table->string('reference', 60)->nullable()->index();

            $table->string('note', 500)->nullable();

            // Attribution — who recorded it. Nulled if the user is later removed.
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Common list filters: a pond's transfers over a date range (either side).
            $table->index(['from_pond_id', 'transferred_on']);
            $table->index(['to_pond_id', 'transferred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pond_transfers');
    }
};