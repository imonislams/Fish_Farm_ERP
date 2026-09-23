<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * feed_purchases — feed bought from a supplier (feed stock IN).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See
 * docs/DATABASE.md §1 and §5.
 *
 * SUPPLIER: there is no `suppliers` table yet (it lands in a later module), so
 * the supplier is stored as a plain `supplier_name` string. When the Suppliers
 * module is built, a migration will add a nullable `supplier_id` FK alongside it
 * and backfill — the same approach used by `fish_stockings.supplier_name`.
 *
 * `total_cost` is DERIVED (quantity_kg × unit_cost) by FeedStockService, so the
 * stored figure can never drift from the line it comes from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feed_type_id')
                ->constrained('feed_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Always in kilograms — the canonical stock unit.
            $table->decimal('quantity_kg', 12, 3);

            $table->decimal('unit_cost', 15, 2)->nullable();

            // Derived and stored: quantity_kg × unit_cost.
            $table->decimal('total_cost', 15, 2)->nullable();

            $table->date('purchased_on')->index();

            $table->string('invoice_no', 100)->nullable();
            $table->string('supplier_name', 150)->nullable();

            // Recorded payment against this purchase (the supplier-due maths
            // belongs to the Finance/Supplier modules; this is the raw figure).
            $table->decimal('paid_amount', 15, 2)->nullable();

            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Common list filter: purchases of a feed type, newest first.
            $table->index(['feed_type_id', 'purchased_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_purchases');
    }
};
