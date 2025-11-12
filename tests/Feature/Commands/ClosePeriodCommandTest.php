<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ClosePeriodCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test command successfully closes a period
     */
    public function test_command_closes_period_successfully(): void
    {
        // Mock the service
        $mockService = Mockery::mock(PeriodClosingServiceInterface::class);
        $mockService->shouldReceive('closePeriod')
            ->once()
            ->with('2025-11')
            ->andReturn([
                'success' => true,
                'message' => 'Period 2025-11 closed successfully.',
                'data' => [
                    'period' => (object)[
                        'period_code' => '2025-11',
                        'status' => 'closed',
                        'locked_at' => now(),
                    ],
                    'balances' => collect([
                        (object)[
                            'account_code' => '1100',
                            'account_name' => 'Cash',
                            'account_type' => 'cash',
                            'total_debit' => 1000.00,
                            'total_credit' => 0.00,
                            'balance' => 1000.00,
                        ],
                    ]),
                    'total_debits' => 1000.00,
                    'total_credits' => 1000.00,
                ]
            ]);

        // Bind the mock
        $this->app->instance(PeriodClosingServiceInterface::class, $mockService);

        // Run the command
        $this->artisan('accounting:close 2025-11')
            ->expectsOutput('Starting closing process for period 2025-11...')
            ->expectsOutput('✓ Period 2025-11 closed successfully.')
            ->assertExitCode(0);
    }

    /**
     * Test command fails when period not found
     */
    public function test_command_fails_when_period_not_found(): void
    {
        $mockService = Mockery::mock(PeriodClosingServiceInterface::class);
        $mockService->shouldReceive('closePeriod')
            ->once()
            ->with('2025-99')
            ->andReturn([
                'success' => false,
                'message' => 'Period 2025-99 not found.',
                'data' => null
            ]);

        $this->app->instance(PeriodClosingServiceInterface::class, $mockService);

        $this->artisan('accounting:close 2025-99')
            ->expectsOutput('Period 2025-99 not found.')
            ->assertExitCode(1);
    }

    /**
     * Test command fails when period already closed
     */
    public function test_command_fails_when_period_already_closed(): void
    {
        $mockService = Mockery::mock(PeriodClosingServiceInterface::class);
        $mockService->shouldReceive('closePeriod')
            ->once()
            ->with('2025-10')
            ->andReturn([
                'success' => false,
                'message' => 'Period 2025-10 is already closed.',
                'data' => null
            ]);

        $this->app->instance(PeriodClosingServiceInterface::class, $mockService);

        $this->artisan('accounting:close 2025-10')
            ->expectsOutput('Period 2025-10 is already closed.')
            ->assertExitCode(1);
    }

    /**
     * Test command displays balance table correctly
     */
    public function test_command_displays_balance_table(): void
    {
        $mockService = Mockery::mock(PeriodClosingServiceInterface::class);
        $mockService->shouldReceive('closePeriod')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Period closed.',
                'data' => [
                    'period' => (object)[
                        'period_code' => '2025-11',
                        'status' => 'closed',
                        'locked_at' => now(),
                    ],
                    'balances' => collect([
                        (object)[
                            'account_code' => '1100',
                            'account_name' => 'Cash Account',
                            'account_type' => 'cash',
                            'total_debit' => 5000.00,
                            'total_credit' => 2000.00,
                            'balance' => 3000.00,
                        ],
                        (object)[
                            'account_code' => '4000',
                            'account_name' => 'Sales Revenue',
                            'account_type' => 'income',
                            'total_debit' => 0.00,
                            'total_credit' => 3000.00,
                            'balance' => -3000.00,
                        ],
                    ]),
                    'total_debits' => 5000.00,
                    'total_credits' => 5000.00,
                ]
            ]);

        $this->app->instance(PeriodClosingServiceInterface::class, $mockService);

        $this->artisan('accounting:close 2025-11')
            ->expectsTable(
                ['Account Code', 'Account Name', 'Type', 'Debits', 'Credits', 'Balance'],
                [
                    ['1100', 'Cash Account', 'cash', '5,000.00', '2,000.00', '3,000.00'],
                    ['4000', 'Sales Revenue', 'income', '0.00', '3,000.00', '-3,000.00'],
                ]
            )
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
