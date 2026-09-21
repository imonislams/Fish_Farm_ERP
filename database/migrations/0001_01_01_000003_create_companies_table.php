<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * companies — the SINGLE business identity for Version 1.
 *
 * Architecture decision (docs/DATABASE.md §1): this application is
 * single-company. Exactly one row is expected. The company is deliberately NOT
 * repeated as a foreign key on business tables — only `users` references it.
 *
 * Do not add tenant middleware, tenant switching or a company selector.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique()->nullable();
            $table->string('logo')->nullable();

            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            $table->string('currency', 10)->default('BDT');
            $table->string('timezone', 64)->default('Asia/Dhaka');

            $table->string('status', 20)->default('active')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};