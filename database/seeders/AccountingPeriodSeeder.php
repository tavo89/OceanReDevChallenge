<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */ 
    public function run(): void
    {
        DB::table('accounting_periods')->insert([
            [
                'period_code' => '2025-10',
                'status' => 'closed',
                'locked_at' => now()->subMonth(),
                'created_at' => now()->subMonth(),
                'updated_at' => now()->subMonth(),
            ],
            [
                'period_code' => '2025-11',
                'status' => 'open',
                'locked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'period_code' => '2025-12',
                'status' => 'open',
                'locked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
