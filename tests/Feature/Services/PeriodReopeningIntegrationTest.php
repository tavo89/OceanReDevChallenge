<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Domain\Accounting\Models\AccountingPeriodBalance;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use App\Domain\Accounting\Contracts\PeriodReopeningServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PeriodReopeningIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reopening_period_deletes_saved_balances(): void
    {
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);

        // Close the period to save balances
        $closingService = app(PeriodClosingServiceInterface::class);
        $closeResult = $closingService->closePeriod('2025-11');
        
        $this->assertTrue($closeResult['success']);

        // Verify balances were saved
        $savedBalances = AccountingPeriodBalance::where('accounting_period_id', 2)->get();
        $this->assertGreaterThan(0, $savedBalances->count(), 'Balances should be saved after closing');
        $balanceCount = $savedBalances->count();

        // Reopen the period
        $reopeningService = app(PeriodReopeningServiceInterface::class);
        $reopenResult = $reopeningService->reopenPeriod('2025-11');

        // Assert period reopened successfully
        $this->assertTrue($reopenResult['success']);
        $this->assertStringContainsString('reopened successfully', $reopenResult['message']);

        // Assert balances were deleted
        $remainingBalances = AccountingPeriodBalance::where('accounting_period_id', 2)->get();
        $this->assertEquals(0, $remainingBalances->count(), 'All balances should be deleted after reopening');

        // Close again to verify balances can be re-saved
        $closeAgainResult = $closingService->closePeriod('2025-11');
        $this->assertTrue($closeAgainResult['success']);

        // Verify balances were saved again
        $newBalances = AccountingPeriodBalance::where('accounting_period_id', 2)->get();
        $this->assertEquals($balanceCount, $newBalances->count(), 'Same number of balances should be saved after re-closing');
    }
}
