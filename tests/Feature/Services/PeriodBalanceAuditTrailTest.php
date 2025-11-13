<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Domain\Accounting\Models\AccountingPeriodBalance;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use App\Domain\Accounting\Contracts\PeriodReopeningServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PeriodBalanceAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deletes_maintain_audit_trail_of_reopened_periods(): void
    {
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);

        $closingService = app(PeriodClosingServiceInterface::class);
        $reopeningService = app(PeriodReopeningServiceInterface::class);

        // First close
        $closingService->closePeriod('2025-11');
        $firstCloseBalances = AccountingPeriodBalance::where('accounting_period_id', 2)->get();
        $firstCloseCount = $firstCloseBalances->count();
        $firstCloseIds = $firstCloseBalances->pluck('id')->toArray();

        $this->assertGreaterThan(0, $firstCloseCount);

        // Reopen (soft deletes the balances)
        $reopeningService->reopenPeriod('2025-11');
        
        // Active balances should be 0
        $activeBalances = AccountingPeriodBalance::where('accounting_period_id', 2)->get();
        $this->assertEquals(0, $activeBalances->count(), 'No active balances after reopening');

        // Soft-deleted balances should still exist
        $trashedBalances = AccountingPeriodBalance::onlyTrashed()
            ->where('accounting_period_id', 2)
            ->get();
        $this->assertEquals($firstCloseCount, $trashedBalances->count(), 'Soft-deleted balances preserved for audit');
        $this->assertNotNull($trashedBalances->first()->deleted_at, 'deleted_at timestamp should be set');

        // Second close (creates new records)
        $closingService->closePeriod('2025-11');
        $secondCloseBalances = AccountingPeriodBalance::where('accounting_period_id', 2)->get();
        $secondCloseIds = $secondCloseBalances->pluck('id')->toArray();

        $this->assertEquals($firstCloseCount, $secondCloseBalances->count());
        
        // New records should have different IDs
        $this->assertNotEquals($firstCloseIds, $secondCloseIds, 'Second close creates new balance records');

        // Total records (including soft-deleted) should be double
        $allBalances = AccountingPeriodBalance::withTrashed()
            ->where('accounting_period_id', 2)
            ->get();
        $this->assertEquals($firstCloseCount * 2, $allBalances->count(), 'Audit trail contains both sets of balances');

        // Verify we can query the history
        $auditHistory = AccountingPeriodBalance::onlyTrashed()
            ->where('accounting_period_id', 2)
            ->orderBy('deleted_at', 'desc')
            ->get();
        
        $this->assertGreaterThan(0, $auditHistory->count(), 'Can retrieve audit history');
    }
}
