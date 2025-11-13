<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use App\Domain\Sales\Contracts\InvoiceServiceInterface;
use App\Domain\Sales\Contracts\InvoiceCancellationServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class BalanceCalculatorWithCancelledInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelled_invoices_are_excluded_from_balance_calculation(): void
    {
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);

        $closingService = app(PeriodClosingServiceInterface::class);
        $invoiceService = app(InvoiceServiceInterface::class);
        $cancellationService = app(InvoiceCancellationServiceInterface::class);

        // Close period to see initial balances
        $result = $closingService->closePeriod('2025-11');
        $this->assertTrue($result['success']);
        
        $initialTotalDebits = $result['data']['total_debits'];
        $initialTotalCredits = $result['data']['total_credits'];

        // Reopen period
        $reopeningService = app(\App\Domain\Accounting\Contracts\PeriodReopeningServiceInterface::class);
        $reopeningService->reopenPeriod('2025-11');

        // Create a new invoice in period 2025-11
        $invoiceResult = $invoiceService->createInvoice([
            'invoice_number' => 'TEST-CANCEL-INV-001',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 5000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 5000.00,
        ]);

        $this->assertTrue($invoiceResult['success']);
        $invoiceId = $invoiceResult['data']->id;

        // Create journal entry for the invoice manually
        DB::table('journal_entries')->insert([
            'entry_number' => 'JE-TEST-001',
            'posting_date' => '2025-11-15',
            'source_reference' => 'invoice:TEST-CANCEL-INV-001',
            'description' => 'Test invoice for cancellation',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journalEntryId = DB::getPdo()->lastInsertId();

        DB::table('journal_entry_lines')->insert([
            [
                'journal_entry_id' => $journalEntryId,
                'account_id' => 1,
                'debit' => 5000.00,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $journalEntryId,
                'account_id' => 2,
                'debit' => 0,
                'credit' => 5000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Close period again - should include the new invoice
        $resultWithInvoice = $closingService->closePeriod('2025-11');
        $this->assertTrue($resultWithInvoice['success']);
        
        $totalDebitsWithInvoice = $resultWithInvoice['data']['total_debits'];
        $totalCreditsWithInvoice = $resultWithInvoice['data']['total_credits'];

        // Totals should be higher than initial (includes new invoice)
        $this->assertGreaterThan($initialTotalDebits, $totalDebitsWithInvoice);
        $this->assertGreaterThan($initialTotalCredits, $totalCreditsWithInvoice);

        // Reopen period again
        $reopeningService->reopenPeriod('2025-11');

        // Cancel the invoice (credit note goes to period 2025-12 which is open)
        $cancelResult = $cancellationService->cancelInvoice($invoiceId, [
            'credit_note_number' => 'CN-TEST-001',
            'period_id' => 3, // 2025-12
            'issue_date' => '2025-12-01',
            'reason' => 'Test cancellation for balance calculation',
        ]);

        $this->assertTrue($cancelResult['success']);

        // Close period again - should exclude the cancelled invoice
        $resultWithoutCancelledInvoice = $closingService->closePeriod('2025-11');
        $this->assertTrue($resultWithoutCancelledInvoice['success']);
        
        $totalDebitsWithoutCancelled = $resultWithoutCancelledInvoice['data']['total_debits'];
        $totalCreditsWithoutCancelled = $resultWithoutCancelledInvoice['data']['total_credits'];

        // Totals should be back to initial (cancelled invoice excluded)
        $this->assertEquals($initialTotalDebits, $totalDebitsWithoutCancelled, 'Debits should match initial after cancellation');
        $this->assertEquals($initialTotalCredits, $totalCreditsWithoutCancelled, 'Credits should match initial after cancellation');
    }
}
