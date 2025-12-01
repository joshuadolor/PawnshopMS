<?php

namespace Database\Seeders;

use App\Models\ItemType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemTypeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $itemTypes = [
            'Jewelry',
            'Electronics',
            'Appliances',
            'Vehicles',
            'Gadgets',
            'Watches',
            'Tools',
            'Musical Instruments',
            'Sports Equipment',
            'Collectibles',
            'Other',
        ];

        foreach ($itemTypes as $itemType) {
            ItemType::firstOrCreate(
                ['name' => $itemType],
                ['name' => $itemType]
            );
        }
    }
}
