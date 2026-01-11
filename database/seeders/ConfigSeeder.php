<?php

namespace Database\Seeders;

use App\Models\Config;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfigSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'key' => 'sangla_service_charge',
                'value' => '5',
                'label' => 'Sangla Service Charge',
                'type' => 'decimal',
                'description' => 'Service charge for Sangla transactions (in currency amount)',
            ],
            [
                'key' => 'sangla_interest_period',
                'value' => 'per_month',
                'label' => 'Sangla Interest Period',
                'type' => 'text',
                'description' => 'Default interest period (per_annum, per_month)',
            ],
            [
                'key' => 'sangla_days_before_redemption',
                'value' => 90,
                'label' => 'Days before Redemption',
                'type' => 'number',
                'description' => 'Days before redemption of Sangla transactions',
            ],
            [
                'key' => 'sangla_days_before_auction_sale',
                'value' => 85,
                'label' => 'Days before Auction Sale',
                'type' => 'number',
                'description' => 'Days before auction sale of Item',
            ],
            [
                'key' => 'grace_period_days',
                'value' => 3,
                'label' => 'Grace Period (Days)',
                'type' => 'number',
                'description' => 'Number of grace period days before additional charges apply',
            ],
        ];

        foreach ($configs as $config) {
            Config::firstOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
