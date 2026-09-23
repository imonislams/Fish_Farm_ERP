<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * parties — generic ledger counterparties not covered by customer/supplier.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `type` is a key from config/finance.php (e.g. landlord, agent, worker, other)
 * so the UI offers a consistent list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);

            // landlord | agent | worker | transporter | other
            $table->string('type', 40)->default('other')->index();

            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();

            // Positive = the party owes the farm.
            $table->decimal('opening_balance', 15, 2)->default(0);

            $table->boolean('is_active')->default(true)->index();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
