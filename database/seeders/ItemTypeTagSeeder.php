<?php

namespace Database\Seeders;

use App\Models\ItemType;
use App\Models\ItemTypeTag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemTypeTagSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jewelry = ItemType::where('name', 'Jewelry')->first();
        
        if ($jewelry) {
            $tags = [
                '10K',
                '14K',
                '16K',
                '18K',
            ];

            foreach ($tags as $tag) {
                ItemTypeTag::firstOrCreate(
                    [
                        'item_type_id' => $jewelry->id,
                        'name' => $tag,
                    ],
                    [
                        'item_type_id' => $jewelry->id,
                        'name' => $tag,
                    ]
                );
            }
        }
    }
}
