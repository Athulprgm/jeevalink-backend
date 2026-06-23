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
        if (!Schema::hasColumn('blood_requests', 'accepted_by')) {
            Schema::table('blood_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('accepted_by')->nullable();
                $table->foreign('accepted_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blood_requests', function (Blueprint $table) {
            $table->dropForeign(['accepted_by']);
            $table->dropColumn('accepted_by');
        });
    }
};
