<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Models\AccountingPeriodBalance;
use App\Domain\Accounting\Models\Account;
use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\Sales\Models\Invoice;
use App\Domain\Sales\Models\Customer;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PeriodClosingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_period_saves_balances_to_database(): void
    {
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);

        // Get the period closing service
        $service = app(PeriodClosingServiceInterface::class);

        // Close the period
        $result = $service->closePeriod('2025-11');

        // Assert period closed successfully
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('closed successfully', $result['message']);

        // Assert balances were saved
        $savedBalances = AccountingPeriodBalance::where('accounting_period_id', 2)->get();
        
        $this->assertGreaterThan(0, $savedBalances->count(), 'Balances should be saved to database');
        
        // Assert balance data integrity
        foreach ($savedBalances as $balance) {
            $this->assertNotNull($balance->account_id);
            $this->assertNotNull($balance->account_code);
            $this->assertNotNull($balance->account_name);
            $this->assertNotNull($balance->account_type);
            $this->assertIsNumeric($balance->total_debit);
            $this->assertIsNumeric($balance->total_credit);
            $this->assertIsNumeric($balance->balance);
        }

        // Assert total debits equal total credits
        $totalDebits = $savedBalances->sum('total_debit');
        $totalCredits = $savedBalances->sum('total_credit');
        $this->assertEquals($totalDebits, $totalCredits, 'Total debits should equal total credits');
    }
}
