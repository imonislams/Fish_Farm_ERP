<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * suppliers — vendors of feed, fingerlings and supplies.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `opening_balance` is money the farm already owed the supplier BEFORE this system
 * started (positive = the farm owes them).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();

            // Positive = the farm owes the supplier.
            $table->decimal('opening_balance', 15, 2)->default(0);

            $table->boolean('is_active')->default(true)->index();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
