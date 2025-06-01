<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppUser;
use Illuminate\Support\Facades\Hash;

class DefaultUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        if (!AppUser::where('username', 'admin')->exists()) {
            AppUser::create([
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '0600000000',
            ]);
            $this->command->info('Admin user created successfully');
        } else {
            $this->command->info('Admin user already exists');
        }

        // Soufiane user
        if (!AppUser::where('username', 'soufiane')->exists()) {
            AppUser::create([
                'name' => 'Soufiane',
                'username' => 'soufiane',
                'email' => 'soufiane@example.com',
                'password' => Hash::make('soufiane123'),
                'role' => 'admin',
                'phone' => '0600000001',
            ]);
            $this->command->info('Soufiane user created successfully');
        } else {
            $this->command->info('Soufiane user already exists');
        }

        // Regular user
        if (!AppUser::where('username', 'user')->exists()) {
            AppUser::create([
                'name' => 'Regular User',
                'username' => 'user',
                'email' => 'user@example.com',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'phone' => '0600000002',
            ]);
            $this->command->info('Regular user created successfully');
        } else {
            $this->command->info('Regular user already exists');
        }
    }
}
