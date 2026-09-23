<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * feed_stock_adjustments — manual correction of feed stock, WITH a reason.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * Stock is never changed silently. An adjustment always records why (a reason
 * key from config/feed.php plus optional free text), who did it and when — so a
 * discrepancy between the book figure and the physical count is always
 * explainable (docs/BUSINESS_LOGIC.md §2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_stock_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feed_type_id')
                ->constrained('feed_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Which way the correction moves stock.
            $table->string('direction', 10)->index();          // in | out

            // Always a positive magnitude in kilograms.
            $table->decimal('quantity_kg', 12, 3);

            // Reason key from config/feed.php (e.g. "count_correction").
            $table->string('reason', 60);

            // Optional free-text explanation for the chosen reason.
            $table->text('note')->nullable();

            $table->date('adjusted_on')->index();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['feed_type_id', 'adjusted_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_stock_adjustments');
    }
};
