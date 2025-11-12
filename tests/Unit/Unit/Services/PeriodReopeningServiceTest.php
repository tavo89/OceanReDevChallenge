<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Domain\Accounting\Services\PeriodReopeningService;
use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use App\Domain\Accounting\Models\AccountingPeriod;
use Mockery;

class PeriodReopeningServiceTest extends TestCase
{
    private PeriodReopeningService $service;
    private $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(AccountingPeriodRepositoryInterface::class);
        
        $this->service = new PeriodReopeningService(
            $this->mockRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test successfully reopens a closed period
     */
    public function test_reopens_period_successfully(): void
    {
        $period = new AccountingPeriod();
        $period->id = 1;
        $period->period_code = '2025-11';
        $period->status = 'closed';
        $period->locked_at = now();
        $period->exists = true;

        $this->mockRepository->shouldReceive('findByCode')
            ->once()
            ->with('2025-11')
            ->andReturn($period);

        $this->mockRepository->shouldReceive('reopenPeriod')
            ->once()
            ->with($period)
            ->andReturnUsing(function ($p) {
                $p->status = 'open';
                $p->locked_at = null;
                return true;
            });

        $result = $this->service->reopenPeriod('2025-11');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('reopened successfully', $result['message']);
        $this->assertArrayHasKey('period', $result['data']);
    }

    /**
     * Test fails when period not found
     */
    public function test_fails_when_period_not_found(): void
    {
        $this->mockRepository->shouldReceive('findByCode')
            ->once()
            ->with('2025-99')
            ->andReturn(null);

        $result = $this->service->reopenPeriod('2025-99');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    /**
     * Test fails when period is already open
     */
    public function test_fails_when_period_already_open(): void
    {
        $period = new AccountingPeriod([
            'id' => 1,
            'period_code' => '2025-11',
            'status' => 'open',
            'locked_at' => null
        ]);
        $period->exists = true;

        $this->mockRepository->shouldReceive('findByCode')
            ->once()
            ->with('2025-11')
            ->andReturn($period);

        $result = $this->service->reopenPeriod('2025-11');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be reopened', $result['message']);
    }

    /**
     * Test canReopenPeriod returns true for closed status
     */
    public function test_can_reopen_period_returns_true_for_closed_status(): void
    {
        $period = new AccountingPeriod([
            'status' => 'closed',
            'locked_at' => now()
        ]);

        $result = $this->service->canReopenPeriod($period);

        $this->assertTrue($result);
    }

    /**
     * Test canReopenPeriod returns false for open status
     */
    public function test_can_reopen_period_returns_false_for_open_status(): void
    {
        $period = new AccountingPeriod([
            'status' => 'open',
            'locked_at' => null
        ]);

        $result = $this->service->canReopenPeriod($period);

        $this->assertFalse($result);
    }
}
