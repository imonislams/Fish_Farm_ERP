<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * sales — a fish sale to a customer (header).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * DERIVED COLUMNS (computed by SalesService, never trusted from input):
 *   total      = subtotal − discount
 *   due_amount = total − paid_amount
 *
 * The whole sale — header, items, and (when those modules are wired) stock
 * movement, customer balance effect and a pond ledger entry — is written inside
 * ONE transaction (docs/BUSINESS_LOGIC.md §6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('invoice_no', 60)->unique();

            $table->date('sale_date')->index();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);

            // paid | partial | due  (key from config/sales.php)
            $table->string('status', 20)->default('due')->index();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['customer_id', 'sale_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
