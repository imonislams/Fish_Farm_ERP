<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customers — buyers of fish.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `opening_balance` is money the customer already owed BEFORE this system started
 * (positive = they owe the farm). It is a real accounting figure, not a derived
 * one, so it is stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();

            // Positive = the customer owes the farm (docs/BUSINESS_LOGIC.md §3).
            $table->decimal('opening_balance', 15, 2)->default(0);

            // Optional maximum credit allowed; null = no limit configured.
            $table->decimal('credit_limit', 15, 2)->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
