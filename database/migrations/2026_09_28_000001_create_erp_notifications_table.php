<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ERP's persistent notification centre.
 *
 * ADDITIVE migration — creates one new table and touches nothing existing.
 * Notifications are generated from real ERP events (a sale recorded, a payment
 * received, low feed stock, an inspection due) by App\Services\Notification\NotificationService.
 *
 * Version 1 is single-company, so there is no company_id; `user_id` NULL means the
 * notification is for everyone on the farm, otherwise it targets one user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('type', 60);                 // sale | purchase | payment | feed_low | inspection_due | …
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('url')->nullable();          // related ERP page, resolved server-side
            $table->string('icon', 40)->default('bell');
            $table->string('tone', 20)->default('info'); // info | success | warning | danger
            $table->timestamp('read_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // The header query is: unread for a user, newest first.
            $table->index(['user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_notifications');
    }
};
