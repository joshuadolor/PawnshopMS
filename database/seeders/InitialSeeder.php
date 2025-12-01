<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialSeeder extends Seeder
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
            'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'password')),
        ]);

        // Seed Item Types
        $this->call(ItemTypeSeeder::class);

        // Seed Branches
        $this->call(BranchSeeder::class);

        // Seed Configurations
        $this->call(ConfigSeeder::class);
    }
}
