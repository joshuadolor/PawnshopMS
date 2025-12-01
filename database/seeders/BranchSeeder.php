<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Grand Daet',
                'address' => 'Daet, Camarines Norte',
                'contact_number' => '',
            ],
            [
                'name' => 'Grand Labo',
                'address' => 'Labo, Camarines Norte',
                'contact_number' => '',
            ],
            [
                'name' => 'Everlucky',
                'address' => '',
                'contact_number' => '',
            ],
            [
                'name' => 'Dolor',
                'address' => '',
                'contact_number' => '',
            ],
            [
                'name' => 'Wiltan',
                'address' => '',
                'contact_number' => '',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(
                ['name' => $branch['name']],
                $branch
            );
        }
    }
}
