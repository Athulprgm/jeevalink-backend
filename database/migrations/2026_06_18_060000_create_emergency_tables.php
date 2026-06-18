<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add FCM and coordinate columns to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token')->nullable();
            }
            if (!Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('users', 'notification_enabled')) {
                $table->boolean('notification_enabled')->default(true);
            }
        });

        // Create emergency_requests table
        Schema::create('emergency_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->string('blood_group', 5);
            $table->integer('units_required')->default(1);
            $table->string('patient_name');
            $table->string('hospital_name');
            $table->string('district');
            $table->string('contact_number');
            $table->text('emergency_message')->nullable();
            $table->string('priority', 20)->default('normal'); // critical, high, normal
            $table->string('status', 20)->default('pending'); // pending, accepted, fulfilled, expired
            $table->timestamp('expires_at');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();

            $table->index('blood_group');
            $table->index('district');
            $table->index('status');
        });

        // Create emergency_responses table
        Schema::create('emergency_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('emergency_requests')->onDelete('cascade');
            $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
            $table->string('response_status', 20)->default('accepted'); // accepted, rejected
            $table->timestamps();

            $table->unique(['request_id', 'donor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_responses');
        Schema::dropIfExists('emergency_requests');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['fcm_token', 'latitude', 'longitude', 'notification_enabled']);
        });
    }
};
