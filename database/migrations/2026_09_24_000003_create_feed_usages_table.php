<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * feed_usages — feed given to a pond (feed stock OUT).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * A usage decreases feed stock and can never take it below zero — the service
 * rejects it before writing (docs/BUSINESS_LOGIC.md §2). It also drives FCR
 * (docs/BUSINESS_LOGIC.md §1), so it is the primary feed input to that module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('feed_type_id')
                ->constrained('feed_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Always in kilograms.
            $table->decimal('quantity_kg', 12, 3);

            $table->date('used_on')->index();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['pond_id', 'used_on']);
            $table->index(['feed_type_id', 'used_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_usages');
    }
};
