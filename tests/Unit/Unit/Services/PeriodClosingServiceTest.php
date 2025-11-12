<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PeriodClosingService;
use App\Contracts\AccountingPeriodRepositoryInterface;
use App\Contracts\BalanceCalculatorInterface;
use App\Models\AccountingPeriod;
use Illuminate\Support\Collection;
use Mockery;

class PeriodClosingServiceTest extends TestCase
{
    private PeriodClosingService $service;
    private $mockRepository;
    private $mockCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        $this->mockCalculator = Mockery::mock(BalanceCalculatorInterface::class);
        
        $this->service = new PeriodClosingService(
            $this->mockRepository,
            $this->mockCalculator
        );
    }

    public function test_closes_period_successfully(): void
    {
        $period = new AccountingPeriod([
            'id' => 1,
            'period_code' => '2025-11',
            'status' => 'open'
        ]);
        $period->id = 1;

        $balances = collect([
            (object)['total_debit' => 1000.00, 'total_credit' => 1000.00]
        ]);

        // Mock repository
        $this->mockRepository->shouldReceive('findByCode')
            ->once()
            ->with('2025-11')
            ->andReturn($period);

        $this->mockRepository->shouldReceive('updateStatus')
            ->once()
            ->with($period, 'locking')
            ->andReturn(true);

        $this->mockRepository->shouldReceive('lockPeriod')
            ->once()
            ->with($period)
            ->andReturn(true);

        // Mock calculator
        $this->mockCalculator->shouldReceive('calculatePeriodBalances')
            ->once()
            ->with(1)
            ->andReturn($balances);

        $this->mockCalculator->shouldReceive('validateBalance')
            ->once()
            ->with($balances)
            ->andReturn(true);

        $this->mockCalculator->shouldReceive('getTotalDebits')
            ->once()
            ->with($balances)
            ->andReturn(1000.00);

        $this->mockCalculator->shouldReceive('getTotalCredits')
            ->once()
            ->with($balances)
            ->andReturn(1000.00);

        // Execute
        $result = $this->service->closePeriod('2025-11');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('closed successfully', $result['message']);
        $this->assertNotNull($result['data']);
    }

    public function test_fails_when_period_not_found(): void
    {
        $this->mockRepository->shouldReceive('findByCode')
            ->once()
            ->with('2025-99')
            ->andReturn(null);

        $result = $this->service->closePeriod('2025-99');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_fails_when_period_already_closed(): void
    {
        $period = new AccountingPeriod([
            'period_code' => '2025-10',
            'status' => 'closed'
        ]);

        $this->mockRepository->shouldReceive('findByCode')
            ->once()
            ->with('2025-10')
            ->andReturn($period);

        $result = $this->service->closePeriod('2025-10');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already closed', $result['message']);
    }

    public function test_fails_when_balances_not_balanced(): void
    {
        $period = new AccountingPeriod([
            'id' => 1,
            'period_code' => '2025-11',
            'status' => 'open'
        ]);
        $period->id = 1;

        $balances = collect([
            (object)['total_debit' => 1000.00, 'total_credit' => 900.00]
        ]);

        $this->mockRepository->shouldReceive('findByCode')
            ->once()
            ->andReturn($period);

        $this->mockRepository->shouldReceive('updateStatus')
            ->once()
            ->andReturn(true);

        $this->mockCalculator->shouldReceive('calculatePeriodBalances')
            ->once()
            ->andReturn($balances);

        $this->mockCalculator->shouldReceive('validateBalance')
            ->once()
            ->andReturn(false);

        $this->mockCalculator->shouldReceive('getTotalDebits')
            ->andReturn(1000.00);

        $this->mockCalculator->shouldReceive('getTotalCredits')
            ->andReturn(900.00);

        $result = $this->service->closePeriod('2025-11');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not balanced', $result['message']);
    }

    public function test_can_close_period_returns_true_for_open_status(): void
    {
        $period = new AccountingPeriod(['status' => 'open']);
        $this->assertTrue($this->service->canClosePeriod($period));
    }

    public function test_can_close_period_returns_false_for_closed_status(): void
    {
        $period = new AccountingPeriod(['status' => 'closed']);
        $this->assertFalse($this->service->canClosePeriod($period));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
