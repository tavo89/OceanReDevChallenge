<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
    }

    public function test_can_create_invoice_via_api(): void
    {
        $response = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-001',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 1500.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 1500.00,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'invoice_number',
                    'issue_date',
                    'due_date',
                    'customer_id',
                    'total_amount',
                    'currency',
                    'period_id',
                ]
            ]);
    }

    public function test_cannot_create_invoice_in_closed_period(): void
    {
        $response = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-002',
            'issue_date' => '2025-10-15',
            'due_date' => '2025-11-15',
            'customer_id' => 1,
            'total_amount' => 2000.00,
            'currency' => 'USD',
            'period_id' => 1, // 2025-10 is closed
            'exchange_rate' => 1.0,
            'base_currency_amount' => 2000.00,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonFragment([
                'message' => 'Cannot create invoice. Accounting period 2025-10 is closed. Only open periods allow transactions.',
            ]);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-003',
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'issue_date',
                'due_date',
                'customer_id',
                'total_amount',
                'currency',
                'period_id',
                'exchange_rate',
                'base_currency_amount',
            ]);
    }

    public function test_validates_unique_invoice_number(): void
    {
        // Create first invoice
        $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-DUPLICATE',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 1000.00,
        ]);

        // Try to create duplicate
        $response = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-DUPLICATE',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 2000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 2000.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['invoice_number']);
    }
}
