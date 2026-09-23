<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pond_ledger_entries — money in/out attributed to a specific pond.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * This is the basis of PER-POND PROFITABILITY (docs/BUSINESS_LOGIC.md §4):
 *
 *   Pond Profit = credits (income)  −  debits (expenses)
 *
 * SIGN CONVENTION: `entry_type` is `debit` (money out / cost attributed to the
 * pond) or `credit` (money in / revenue attributed to the pond). The balance
 * maths lives once in App\Services\Finance\LedgerRules and is applied by
 * App\Services\Pond\PondLedgerService — never recomputed in a view.
 *
 * TRACEABILITY: every entry records `source_type` + `source_id` so it can be
 * traced back to the transaction that produced it. Entries are written by the
 * service that owns that transaction — never ad-hoc by a controller. A manual
 * entry (no owning module yet) records source_type = 'manual'.
 *
 * `source_type`/`source_id` are a plain polymorphic-style pair (string + id),
 * NOT a foreign key: the sources are different tables and one of them is a human
 * entry with no row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pond_ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pond_id')
                ->constrained('ponds')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // debit = cost attributed to the pond; credit = revenue attributed to it.
            $table->string('entry_type', 10)->index();          // debit | credit

            // Category key from config/ledger.php (feed, labour, medicine, sale, …).
            $table->string('category', 60)->index();

            // Money — decimal, never float.
            $table->decimal('amount', 15, 2);

            $table->date('entry_date')->index();

            $table->string('reference', 100)->nullable();

            // Traceability back to the originating transaction.
            $table->string('source_type', 40)->default('manual')->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Common reporting filter: one pond's entries over a date range.
            $table->index(['pond_id', 'entry_date']);
            $table->index(['entry_type', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pond_ledger_entries');
    }
};
