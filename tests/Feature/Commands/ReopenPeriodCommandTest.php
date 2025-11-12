<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Contracts\PeriodReopeningServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ReopenPeriodCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test command successfully reopens a period
     */
    public function test_command_reopens_period_successfully(): void
    {
        // Mock the service
        $mockService = Mockery::mock(PeriodReopeningServiceInterface::class);
        $mockService->shouldReceive('reopenPeriod')
            ->once()
            ->with('2025-11')
            ->andReturn([
                'success' => true,
                'message' => 'Period 2025-11 reopened successfully.',
                'data' => [
                    'period' => (object)[
                        'period_code' => '2025-11',
                        'status' => 'open',
                        'locked_at' => null
                    ]
                ]
            ]);

        $this->app->instance(PeriodReopeningServiceInterface::class, $mockService);

        // Execute command with confirmation
        $this->artisan('accounting:reopen', ['period' => '2025-11'])
            ->expectsConfirmation('Are you sure you want to reopen period 2025-11? This will allow modifications to closed transactions.', 'yes')
            ->expectsOutput('✓ Period 2025-11 reopened successfully.')
            ->assertExitCode(0);
    }

    /**
     * Test command fails when period not found
     */
    public function test_command_fails_when_period_not_found(): void
    {
        $mockService = Mockery::mock(PeriodReopeningServiceInterface::class);
        $mockService->shouldReceive('reopenPeriod')
            ->once()
            ->with('2025-99')
            ->andReturn([
                'success' => false,
                'message' => 'Period 2025-99 not found.',
                'data' => []
            ]);

        $this->app->instance(PeriodReopeningServiceInterface::class, $mockService);

        $this->artisan('accounting:reopen', ['period' => '2025-99'])
            ->expectsConfirmation('Are you sure you want to reopen period 2025-99? This will allow modifications to closed transactions.', 'yes')
            ->assertExitCode(1);
    }

    /**
     * Test command fails when period already open
     */
    public function test_command_fails_when_period_already_open(): void
    {
        $mockService = Mockery::mock(PeriodReopeningServiceInterface::class);
        $mockService->shouldReceive('reopenPeriod')
            ->once()
            ->with('2025-11')
            ->andReturn([
                'success' => false,
                'message' => 'Period 2025-11 cannot be reopened. Status: open',
                'data' => [
                    'period' => (object)[
                        'period_code' => '2025-11',
                        'status' => 'open',
                        'locked_at' => null
                    ]
                ]
            ]);

        $this->app->instance(PeriodReopeningServiceInterface::class, $mockService);

        $this->artisan('accounting:reopen', ['period' => '2025-11'])
            ->expectsConfirmation('Are you sure you want to reopen period 2025-11? This will allow modifications to closed transactions.', 'yes')
            ->assertExitCode(1);
    }

    /**
     * Test command cancelled when user declines confirmation
     */
    public function test_command_cancelled_when_user_declines(): void
    {
        $mockService = Mockery::mock(PeriodReopeningServiceInterface::class);
        $mockService->shouldNotReceive('reopenPeriod');

        $this->app->instance(PeriodReopeningServiceInterface::class, $mockService);

        $this->artisan('accounting:reopen', ['period' => '2025-11'])
            ->expectsConfirmation('Are you sure you want to reopen period 2025-11? This will allow modifications to closed transactions.', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
