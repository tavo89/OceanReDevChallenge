<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountingPeriodBalanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);
        
        // Close a period to generate balances
        $closingService = app(PeriodClosingServiceInterface::class);
        $closingService->closePeriod('2025-11');
    }

    public function test_can_get_all_period_balances(): void
    {
        $response = $this->getJson('/api/accounting/period-balances');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Accounting period balances retrieved successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'count',
                    'balances' => [
                        '*' => [
                            'id',
                            'period' => [
                                'id',
                                'code',
                                'status',
                            ],
                            'account' => [
                                'id',
                                'code',
                                'name',
                                'type',
                            ],
                            'totals' => [
                                'debit',
                                'credit',
                                'balance',
                            ],
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]
            ]);

        $this->assertGreaterThan(0, $response->json('data.count'));
    }

    public function test_can_filter_balances_by_period(): void
    {
        $response = $this->getJson('/api/accounting/period-balances?period_id=2');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify all returned balances are for period 2
        $balances = $response->json('data.balances');
        foreach ($balances as $balance) {
            $this->assertEquals(2, $balance['period']['id']);
        }
    }

    public function test_can_filter_balances_by_account(): void
    {
        $response = $this->getJson('/api/accounting/period-balances?account_id=1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify all returned balances are for account 1
        $balances = $response->json('data.balances');
        foreach ($balances as $balance) {
            $this->assertEquals(1, $balance['account']['id']);
        }
    }

    public function test_returns_empty_array_when_no_balances(): void
    {
        // Query for non-existent period
        $response = $this->getJson('/api/accounting/period-balances?period_id=999');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'count' => 0,
                    'balances' => []
                ]
            ]);
    }
}
