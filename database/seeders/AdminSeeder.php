<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Create the default JeevaLink admin account.
     *
     * Credentials:
     *   Email    : admin@jeevalink.org
     *   Password : Admin@2026
     */
    public function run(): void
    {
        // Avoid duplicate seeding
        if (DB::table('users')->where('email', 'admin@jeevalink.org')->exists()) {
            $this->command->info('Admin user already exists. Skipping.');
            return;
        }

        DB::table('users')->insert([
            'full_name'              => 'JeevaLink Admin',
            'email'                  => 'admin@jeevalink.org',
            'mobile'                 => '9000000001',
            'password_hash'          => Hash::make('Admin@2026'),
            'role'                   => 'admin',
            'blood_group'            => 'N/A',
            'city'                   => 'Kochi',
            'district'               => 'Ernakulam',
            'status'                 => 'Active',
            'is_verified'            => true,
            'available_for_donation' => false,
            'reward_points'          => 0,
            'lives_saved'            => 0,
            'total_donations'        => 0,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $this->command->info('✅ Admin user created successfully!');
        $this->command->info('   Email    : admin@jeevalink.org');
        $this->command->info('   Password : Admin@2026');
    }
}
