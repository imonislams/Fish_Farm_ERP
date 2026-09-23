<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customer_payments — money received from a customer.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A payment REDUCES the customer's due. `method` is a key from config/finance.php
 * (cash, bank, bkash, …) so the UI offers a consistent list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Optional: a payment may be tied to a specific invoice.
            $table->foreignId('sale_id')->nullable()
                ->constrained('sales')->nullOnDelete();

            $table->decimal('amount', 15, 2);
            $table->string('method', 30)->default('cash');
            $table->date('paid_on')->index();
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['customer_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
