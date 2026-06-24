<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add volunteer-specific fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('organization_name')->nullable()->after('district');
            $table->string('volunteer_type')->nullable()->after('organization_name');
            $table->string('secondary_phone')->nullable()->after('mobile');
            $table->string('pin_code', 10)->nullable()->after('city');
            $table->text('address')->nullable()->after('pin_code');
            $table->text('remarks')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['organization_name', 'volunteer_type', 'secondary_phone', 'pin_code', 'address', 'remarks']);
        });
    }
};
