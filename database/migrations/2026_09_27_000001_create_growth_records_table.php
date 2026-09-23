<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * growth_records — sampled average fish weight over time, per pond.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * These samples supply the "current weight" for FCR and the growth chart. FCR
 * compares the LATEST sample against the stocking's average weight, so a missing
 * sample is meaningful — it means weight gain cannot be established, and FCR must
 * render as "—" rather than a guess (docs/BUSINESS_LOGIC.md §1).
 *
 * Incremental migration — no existing table is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growth_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->date('sampled_on')->index();

            // Average weight of one fish, in grams. Required — a sample without a
            // weight measures nothing.
            $table->decimal('avg_weight_g', 10, 2);

            // How many fish were weighed. Optional context for sampling confidence.
            $table->unsignedInteger('sample_size')->nullable();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Common list filter: one pond's samples, newest first.
            $table->index(['pond_id', 'sampled_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_records');
    }
};
