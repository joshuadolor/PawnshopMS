<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Superadmin
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => env('SUPERADMIN_USERNAME', 'superadmin'),
            'phone_number' => '1234567890',
            'email' => null,
            'role' => 'superadmin',
            'is_active' => true,
            'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'password')),
        ]);

        // Create Admin
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'username' => 'admin',
            'phone_number' => '1234567891',
            'email' => null,
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        // Create Staff
        User::create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'username' => 'staff',
            'phone_number' => '1234567892',
            'email' => null,
            'role' => 'staff',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        // Seed Item Types
        $this->call(ItemTypeSeeder::class);

        // Seed Item Type Subtypes
        $this->call(ItemTypeSubtypeSeeder::class);

        // Seed Branches
        $this->call(BranchSeeder::class);

        // Seed Configurations
        $this->call(ConfigSeeder::class);
    }
}
