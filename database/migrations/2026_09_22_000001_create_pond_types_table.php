<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pond_types — master data classifying ponds (nursery, grow-out, brood, …).
 *
 * Version 1 is SINGLE COMPANY: there is no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * Incremental migration — no existing table is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pond_types', function (Blueprint $table) {
            $table->id();

            // Unique: two pond types with the same name would be ambiguous.
            $table->string('name', 100)->unique();

            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pond_types');
    }
};
