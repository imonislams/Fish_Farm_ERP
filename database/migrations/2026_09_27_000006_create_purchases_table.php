<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * purchases — a purchase from a supplier (header).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * DERIVED COLUMNS (computed by PurchaseService, never trusted from input):
 *   total      = subtotal − discount
 *   due_amount = total − paid_amount
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('invoice_no', 60)->nullable();

            $table->date('purchase_date')->index();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('due_amount', 15, 2)->default(0);

            // paid | partial | due
            $table->string('status', 20)->default('due')->index();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['supplier_id', 'purchase_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
