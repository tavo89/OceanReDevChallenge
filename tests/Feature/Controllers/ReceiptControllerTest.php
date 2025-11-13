<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReceiptControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
    }

    public function test_can_create_receipt_via_api(): void
    {
        $response = $this->postJson('/api/sales/receipts', [
            'receipt_number' => 'API-RCP-001',
            'payment_date' => '2025-11-20',
            'amount' => 750.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 750.00,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Receipt created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'receipt_number',
                    'payment_date',
                    'amount',
                    'currency',
                    'period_id',
                ]
            ]);
    }

    public function test_cannot_create_receipt_in_closed_period(): void
    {
        $response = $this->postJson('/api/sales/receipts', [
            'receipt_number' => 'API-RCP-002',
            'payment_date' => '2025-10-20',
            'amount' => 500.00,
            'currency' => 'USD',
            'period_id' => 1, // 2025-10 is closed
            'exchange_rate' => 1.0,
            'base_currency_amount' => 500.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment([
                'message' => 'Cannot create receipt. Accounting period 2025-10 is closed. Only open periods allow transactions.',
            ]);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/sales/receipts', [
            'receipt_number' => 'API-RCP-003',
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'payment_date',
                'amount',
                'currency',
                'period_id',
                'exchange_rate',
                'base_currency_amount',
            ]);
    }
}
