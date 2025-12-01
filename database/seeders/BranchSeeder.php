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
            ],
            [
                'name' => 'Grand Labo',
            ],
            [
                'name' => 'Everlucky',
            ],
            [
                'name' => 'Dolor',
            ],
            [
                'name' => 'Wiltan',
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
