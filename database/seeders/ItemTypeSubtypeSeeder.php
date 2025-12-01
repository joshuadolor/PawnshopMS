<?php

namespace Database\Seeders;

use App\Models\ItemType;
use App\Models\ItemTypeSubtype;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemTypeSubtypeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jewelry = ItemType::where('name', 'Jewelry')->first();
        
        if ($jewelry) {
            $subtypes = [
                'Anklet',
                'Bracelet',
                'Choker',
                'Necklace without a pendant',
                'Necklace with pendant',
                'Pendant',
                'Ring',
                'Other',
            ];

            foreach ($subtypes as $subtype) {
                ItemTypeSubtype::firstOrCreate(
                    [
                        'item_type_id' => $jewelry->id,
                        'name' => $subtype,
                    ],
                    [
                        'item_type_id' => $jewelry->id,
                        'name' => $subtype,
                    ]
                );
            }
        }
    }
}
