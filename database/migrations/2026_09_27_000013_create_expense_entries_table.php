<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * expense_entries — recorded expenses (feed, labour, medicine, electricity, …).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `pond_id` is NULLABLE: a general/farm-wide expense has no pond; a pond expense
 * also attributes the money to that pond's ledger (docs/BUSINESS_LOGIC.md §4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')->nullable()
                ->constrained('ponds')->nullOnDelete();

            $table->foreignId('expense_category_id')
                ->constrained('expense_categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('amount', 15, 2);
            $table->date('entry_date')->index();

            $table->string('reference', 100)->nullable();
            $table->string('paid_to', 150)->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['expense_category_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_entries');
    }
};
