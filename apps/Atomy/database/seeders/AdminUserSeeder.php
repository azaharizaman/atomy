<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Admin User Seeder
 * 
 * Creates default admin user with necessary permissions for Finance panel access.
 * For development and testing only.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $adminUser = \App\Models\User::withoutGlobalScopes()
            ->where('email', 'admin@nexus.local')
            ->first();

        if ($adminUser) {
            $this->command->info('Admin user already exists.');
            return;
        }

        // Create admin user
        $adminUser = \App\Models\User::create([
            'email' => 'admin@nexus.local',
            'password_hash' => password_hash('password', PASSWORD_BCRYPT),
            'name' => 'System Administrator',
            'status' => 'active',
            'email_verified_at' => now(),
            'mfa_enabled' => false,
        ]);

        // Check if admin role exists
        $adminRole = \App\Models\Role::where('name', 'admin')->first();

        if (!$adminRole) {
            // Create admin role
            $adminRole = \App\Models\Role::create([
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access with all permissions',
            ]);

            $this->command->info('Created admin role.');
        }

        // Assign admin role to user
        $adminUser->roles()->attach($adminRole);

        $this->command->info('Admin user created successfully:');
        $this->command->info('Email: admin@nexus.local');
        $this->command->info('Password: password');
        $this->command->warn('IMPORTANT: Change this password in production!');
    }
}
