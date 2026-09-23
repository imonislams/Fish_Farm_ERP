<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fish_batches — a stocking cycle for one pond + species.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * IMPORTANT — SINGLE SOURCE OF TRUTH (docs/BUSINESS_LOGIC.md §2):
 *   This table is a LABEL/grouping for a cycle. It does NOT store a running
 *   quantity. `initial_quantity` records what was put in at the start so a batch
 *   can report its own survival, but the CURRENT quantity of the batch is always
 *   the sum of the stockings tagged to it minus the mortalities/harvests tagged to
 *   it — computed by FishBatchService, never stored here. There is exactly one
 *   authority for fish counts (FishStockService + the movement records); the batch
 *   is a view over them, not a second ledger.
 *
 * Stocking rows carry `fish_batch_id` (added in the next migration) so a batch's
 * movements are simply the records already tagged to it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fish_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('fish_species_id')
                ->constrained('fish_species')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('code', 40)->unique();

            $table->date('started_on')->index();
            $table->date('ended_on')->nullable();

            // What was put in when the cycle began (the survival denominator).
            $table->unsignedInteger('initial_quantity')->default(0);

            // active | completed | harvested | cancelled  (config key `batch_statuses`).
            $table->string('status', 20)->default('active')->index();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['pond_id', 'status']);
            $table->index(['fish_species_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_batches');
    }
};
