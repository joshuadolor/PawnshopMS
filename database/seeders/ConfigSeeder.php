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
                'key' => 'sangla_interest_rate',
                'value' => '3',
                'label' => 'Sangla Default Interest Rate (%)',
                'type' => 'percentage',
                'description' => 'Default interest rate for Sangla transactions (percentage)',
            ],
            [
                'key' => 'sangla_interest_period',
                'value' => 'per_month',
                'label' => 'Sangla Interest Period',
                'type' => 'text',
                'description' => 'Default interest period (per_annum, per_month, others)',
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
