<?php

namespace Database\Seeders;

use App\Models\AdditionalChargeConfig;
use Illuminate\Database\Seeder;

class AdditionalChargeConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdditionalChargeConfig::truncate();
        // LD (Late Days) configurations for renewal transactions
        AdditionalChargeConfig::create([
            'start_day' => 4,
            'end_day' => 31,
            'percentage' => 2.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 32,
            'end_day' => 61,
            'percentage' => 4.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 62,
            'end_day' => 90,
            'percentage' => 6.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 91,
            'end_day' => 120,
            'percentage' => 8.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 121,
            'end_day' => 150,
            'percentage' => 10.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 151,
            'end_day' => 180,
            'percentage' => 12.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 181,
            'end_day' => 210,
            'percentage' => 14.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 211,
            'end_day' => 240,
            'percentage' => 16.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 241,
            'end_day' => 270,
            'percentage' => 18.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 271,
            'end_day' => 300,
            'percentage' => 20.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 301,
            'end_day' => 330,
            'percentage' => 22.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 331,
            'end_day' => 360,
            'percentage' => 24.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 361,
            'end_day' => 390,
            'percentage' => 26.00,
            'type' => 'LD',
            'transaction_type' => 'renewal',
        ]);

        // EC (Exceeded Charge) configurations for renewal transactions
        AdditionalChargeConfig::create([
            'start_day' => 92,
            'end_day' => 100,
            'percentage' => 2.50,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 101,
            'end_day' => 120,
            'percentage' => 5.00,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 121,
            'end_day' => 140,
            'percentage' => 7.50,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 141,
            'end_day' => 160,
            'percentage' => 10.00,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 161,
            'end_day' => 180,
            'percentage' => 12.50,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 181,
            'end_day' => 200,
            'percentage' => 15.00,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 201,
            'end_day' => 220,
            'percentage' => 17.50,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 221,
            'end_day' => 240,
            'percentage' => 20.00,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 241,
            'end_day' => 260,
            'percentage' => 22.50,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 261,
            'end_day' => 280,
            'percentage' => 25.00,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 281,
            'end_day' => 300,
            'percentage' => 27.50,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 301,
            'end_day' => 320,
            'percentage' => 30.00,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 321,
            'end_day' => 340,
            'percentage' => 32.50,
            'type' => 'EC',
            'transaction_type' => 'renewal',
        ]);

        // LD (Late Days) configurations for tubos transactions
        AdditionalChargeConfig::create([
            'start_day' => 1,
            'end_day' => 31,
            'percentage' => 2.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 32,
            'end_day' => 61,
            'percentage' => 4.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 62,
            'end_day' => 90,
            'percentage' => 6.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 91,
            'end_day' => 120,
            'percentage' => 8.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 121,
            'end_day' => 150,
            'percentage' => 10.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 151,
            'end_day' => 180,
            'percentage' => 12.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 181,
            'end_day' => 210,
            'percentage' => 14.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 211,
            'end_day' => 240,
            'percentage' => 16.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 241,
            'end_day' => 270,
            'percentage' => 18.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 271,
            'end_day' => 300,
            'percentage' => 20.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 301,
            'end_day' => 330,
            'percentage' => 22.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 331,
            'end_day' => 360,
            'percentage' => 24.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 361,
            'end_day' => 390,
            'percentage' => 26.00,
            'type' => 'LD',
            'transaction_type' => 'tubos',
        ]);

        // EC (Exceeded Charge) configurations for tubos transactions
        AdditionalChargeConfig::create([
            'start_day' => 92,
            'end_day' => 100,
            'percentage' => 2.50,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 101,
            'end_day' => 120,
            'percentage' => 5.00,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 121,
            'end_day' => 140,
            'percentage' => 7.50,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 141,
            'end_day' => 160,
            'percentage' => 10.00,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 161,
            'end_day' => 180,
            'percentage' => 12.50,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 181,
            'end_day' => 200,
            'percentage' => 15.00,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 201,
            'end_day' => 220,
            'percentage' => 17.50,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 221,
            'end_day' => 240,
            'percentage' => 20.00,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 241,
            'end_day' => 260,
            'percentage' => 22.50,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 261,
            'end_day' => 280,
            'percentage' => 25.00,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 281,
            'end_day' => 300,
            'percentage' => 27.50,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 301,
            'end_day' => 320,
            'percentage' => 30.00,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);

        AdditionalChargeConfig::create([
            'start_day' => 321,
            'end_day' => 340,
            'percentage' => 32.50,
            'type' => 'EC',
            'transaction_type' => 'tubos',
        ]);
    }
}
