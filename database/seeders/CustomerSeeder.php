<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */ 
    public function run(): void
    {
        DB::table('customers')->insert([
            [
                'customer_code' => 'CUST001',
                'name' => 'ABC Corporation',
                'email' => 'contact@abc.com',
                'phone' => '555-0001',
                'address' => '123 Main St, City, State',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_code' => 'CUST002',
                'name' => 'XYZ Industries',
                'email' => 'info@xyz.com',
                'phone' => '555-0002',
                'address' => '456 Oak Ave, City, State',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_code' => 'CUST003',
                'name' => 'Global Solutions Ltd',
                'email' => 'hello@global.com',
                'phone' => '555-0003',
                'address' => '789 Pine Rd, City, State',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
