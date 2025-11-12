<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('accounts')->insert([
            [
                'account_code' => '1100',
                'name' => 'Cash',
                'type' => 'cash',
                'is_postable' => true,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_code' => '1200',
                'name' => 'Accounts Receivable',
                'type' => 'receivable',
                'is_postable' => true,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_code' => '4000',
                'name' => 'Sales Revenue',
                'type' => 'income',
                'is_postable' => true,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_code' => '4100',
                'name' => 'Service Revenue',
                'type' => 'income',
                'is_postable' => true,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
