<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReceiptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $period2025_11 = DB::table('accounting_periods')->where('period_code', '2025-11')->first();
        $period2025_10 = DB::table('accounting_periods')->where('period_code', '2025-10')->first();

        // Common payment date for November 2025 receipts
        $paymentDate = '2025-11-20';

        DB::table('receipts')->insert([
            // November 2025 receipts (same payment date)
            [
                'receipt_number' => 'RCPT-2025-001',
                'payment_date' => $paymentDate,
                'amount' => 1500.00,
                'currency' => 'USD',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 1.000000,
                'base_currency_amount' => 1500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'receipt_number' => 'RCPT-2025-002',
                'payment_date' => $paymentDate,
                'amount' => 2500.00,
                'currency' => 'USD',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 1.000000,
                'base_currency_amount' => 2500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'receipt_number' => 'RCPT-2025-003',
                'payment_date' => $paymentDate,
                'amount' => 1200.00,
                'currency' => 'USD',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 1.000000,
                'base_currency_amount' => 1200.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'receipt_number' => 'RCPT-2025-004',
                'payment_date' => $paymentDate,
                'amount' => 850.00,
                'currency' => 'EUR',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 0.850000,
                'base_currency_amount' => 1000.00, // 850 EUR = 1000 USD
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'receipt_number' => 'RCPT-2025-005',
                'payment_date' => $paymentDate,
                'amount' => 3200.00,
                'currency' => 'USD',
                'period_id' => $period2025_11->id,
                'exchange_rate' => 1.000000,
                'base_currency_amount' => 3200.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // October 2025 receipts (different date)
            [
                'receipt_number' => 'RCPT-2025-OLD-001',
                'payment_date' => '2025-10-25',
                'amount' => 1800.00,
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
