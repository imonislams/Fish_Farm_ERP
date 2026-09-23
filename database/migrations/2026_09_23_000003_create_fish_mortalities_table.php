<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fish_mortalities — recorded fish deaths (stock OUT).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * QUANTITY is an integer count. A mortality can never take a pond's stock below
 * zero — the service rejects it before anything is written
 * (docs/BUSINESS_LOGIC.md §2).
 *
 * `cause` is a free-form short string validated against config/fish.php so the
 * UI offers a consistent list without hard-coding strings in the view.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fish_mortalities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->unsignedInteger('quantity');

            // Average weight of one dead fish, in grams (optional context).
            $table->decimal('avg_weight_g', 10, 2)->nullable();

            $table->date('recorded_on')->index();

            $table->string('cause', 100)->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['pond_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fish_mortalities');
    }
};
