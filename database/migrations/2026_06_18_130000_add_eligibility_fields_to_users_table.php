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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'eligibility_status')) {
                $table->string('eligibility_status')->default('Pending Check');
            }
            if (!Schema::hasColumn('users', 'eligibility_checked_at')) {
                $table->timestamp('eligibility_checked_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'eligibility_status')) {
                $table->dropColumn('eligibility_status');
            }
            if (Schema::hasColumn('users', 'eligibility_checked_at')) {
                $table->dropColumn('eligibility_checked_at');
            }
        });
    }
};
