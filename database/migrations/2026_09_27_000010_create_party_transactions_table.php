<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * party_transactions — debit/credit entries against a party.
 *
 * Version 1 is SINGLE COMPANY: no company_id/farm_id column. See docs/DATABASE.md §1.
 *
 * SIGN CONVENTION (docs/BUSINESS_LOGIC.md §3, applied via Finance\LedgerRules):
 *   debit  = the party owes the farm more (or the farm paid them)
 *   credit = the farm owes the party more / they settled
 *   Balance = Σ debits − Σ credits.  Positive = they owe the farm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('party_id')
                ->constrained('parties')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            // debit | credit
            $table->string('entry_type', 10)->index();

            $table->decimal('amount', 15, 2);
            $table->date('entry_date')->index();
            $table->string('reference', 100)->nullable();
            $table->string('description', 255)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['party_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_transactions');
    }
};
