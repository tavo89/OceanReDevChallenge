<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JournalEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get accounts for journal entry lines
        $cashAccount = DB::table('accounts')->where('account_code', '1100')->first();
        $receivableAccount = DB::table('accounts')->where('account_code', '1200')->first();
        $salesRevenueAccount = DB::table('accounts')->where('account_code', '4000')->first();
        
        // Get invoices
        $invoices = DB::table('invoices')->get();
        
        // Create journal entries for each invoice
        foreach ($invoices as $invoice) {
            // Insert journal entry
            $journalEntryId = DB::table('journal_entries')->insertGetId([
                'entry_number' => 'JE-INV-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT),
                'posting_date' => $invoice->issue_date,
                'description' => 'Invoice ' . $invoice->invoice_number,
                'source_reference' => 'invoice:' . $invoice->invoice_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Create journal entry lines (Debit A/R, Credit Revenue)
            DB::table('journal_entry_lines')->insert([
                [
                    'journal_entry_id' => $journalEntryId,
                    'account_id' => $receivableAccount->id,
                    'debit' => $invoice->base_currency_amount,
                    'credit' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'journal_entry_id' => $journalEntryId,
                    'account_id' => $salesRevenueAccount->id,
                    'debit' => 0,
                    'credit' => $invoice->base_currency_amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
        
        // Get receipts
        $receipts = DB::table('receipts')->get();
        
        // Create journal entries for each receipt
        foreach ($receipts as $receipt) {
            // Insert journal entry
            $journalEntryId = DB::table('journal_entries')->insertGetId([
                'entry_number' => 'JE-RCPT-' . str_pad($receipt->id, 6, '0', STR_PAD_LEFT),
                'posting_date' => $receipt->payment_date,
                'description' => 'Receipt ' . $receipt->receipt_number,
                'source_reference' => 'receipt:' . $receipt->receipt_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Create journal entry lines (Debit Cash, Credit A/R)
            DB::table('journal_entry_lines')->insert([
                [
                    'journal_entry_id' => $journalEntryId,
                    'account_id' => $cashAccount->id,
                    'debit' => $receipt->base_currency_amount,
                    'credit' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'journal_entry_id' => $journalEntryId,
                    'account_id' => $receivableAccount->id,
                    'debit' => 0,
                    'credit' => $receipt->base_currency_amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
