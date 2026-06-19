<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the notification_logs table for tracking FCM delivery attempts.
     */
    public function up(): void
    {
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->text('fcm_token')->nullable();
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->json('data')->nullable();
                // 'sent', 'failed', 'invalid_token', 'simulated'
                $table->string('status', 30)->default('pending')->index();
                $table->string('fcm_message_id')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('attempt')->default(1);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
