<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * income_entries — income not tied to a sale (misc. farm income).
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * `pond_id` is NULLABLE: some income belongs to a pond, some is farm-wide. When a
 * pond is given, FinanceService also attributes the money to that pond's ledger so
 * per-pond profitability stays correct (docs/BUSINESS_LOGIC.md §4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')->nullable()
                ->constrained('ponds')->nullOnDelete();

            // Key from config/finance.php (subsidy, misc_sale, rent, other, …).
            $table->string('category', 60)->default('other')->index();

            $table->decimal('amount', 15, 2);
            $table->date('entry_date')->index();
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['category', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_entries');
    }
};
