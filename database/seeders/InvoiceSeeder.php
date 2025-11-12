<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */ 
    public function run(): void
    {
        $period2025_11 = DB::table('accounting_periods')->where('period_code', '2025-11')->first();
        $period2025_10 = DB::table('accounting_periods')->where('period_code', '2025-10')->first();

        // Common date for November 2025 invoices
        $issueDate = '2025-11-15';
        $dueDate = '2025-12-15';

        DB::table('invoices')->insert([
            // November 2025 invoices (same issue date)
            [
                'invoice_number' => 'INV-2025-001',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'customer_id' => 1,
                'total_amount' => 1500.00,
                'currency' => 'USD',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 1.000000,
                'base_currency_amount' => 1500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice_number' => 'INV-2025-002',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'customer_id' => 2,
                'total_amount' => 2500.00,
                'currency' => 'USD',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 1.000000,
                'base_currency_amount' => 2500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice_number' => 'INV-2025-003',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'customer_id' => 3,
                'total_amount' => 3200.00,
                'currency' => 'USD',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 1.000000,
                'base_currency_amount' => 3200.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'invoice_number' => 'INV-2025-004',
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'customer_id' => 1,
                'total_amount' => 850.00,
                'currency' => 'EUR',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 0.850000,
                'base_currency_amount' => 1000.00, // 850 EUR = 1000 USD
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // October 2025 invoices (different date)
            [
                'invoice_number' => 'INV-2025-OLD-001',
                'issue_date' => '2025-10-20',
                'due_date' => '2025-11-20',
                'customer_id' => 2,
                'total_amount' => 1800.00,
                'currency' => 'USD',
                'period_id' => $period2025_10->id,
                'exchange_rate' => 1.000000,
                'base_currency_amount' => 1800.00,
                'created_at' => now()->subMonth(),
                'updated_at' => now()->subMonth(),
            ],
        ]);
    }
}
