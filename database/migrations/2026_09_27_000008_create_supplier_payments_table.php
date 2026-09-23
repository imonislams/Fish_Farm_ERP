<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * supplier_payments — money paid to a supplier.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * A payment REDUCES the supplier due.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('purchase_id')->nullable()
                ->constrained('purchases')->nullOnDelete();

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

            $table->index(['supplier_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
