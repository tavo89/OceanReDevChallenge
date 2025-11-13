<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceCancellationControllerTest extends TestCase
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

    public function test_can_cancel_invoice_via_api(): void
    {
        // Create invoice first
        $createResponse = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-CANCEL-001',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 1500.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 1500.00,
        ]);

        $this->assertEquals(201, $createResponse->status());
        $invoiceId = $createResponse->json('data.id');

        // Cancel invoice
        $response = $this->postJson("/api/sales/invoices/{$invoiceId}/cancel", [
            'credit_note_number' => 'API-CN-001',
            'period_id' => 2,
            'issue_date' => '2025-11-20',
            'reason' => 'Customer request via API',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Invoice cancelled successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'invoice' => [
                        'id',
                        'status',
                        'cancelled_at',
                    ],
                    'credit_note' => [
                        'id',
                        'credit_note_number',
                        'invoice_id',
                        'amount',
                    ]
                ]
            ]);

        $this->assertEquals('cancelled', $response->json('data.invoice.status'));
    }

    public function test_validates_required_fields_for_cancellation(): void
    {
        // Create invoice
        $createResponse = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-CANCEL-002',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 2000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 2000.00,
        ]);

        $invoiceId = $createResponse->json('data.id');

        // Try to cancel without required fields
        $response = $this->postJson("/api/sales/invoices/{$invoiceId}/cancel", [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'credit_note_number',
                'period_id',
            ]);
    }

    public function test_cannot_cancel_with_duplicate_credit_note_number(): void
    {
        // Create and cancel first invoice
        $invoice1Response = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-CANCEL-003',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 1000.00,
        ]);

        $invoice1Id = $invoice1Response->json('data.id');

        $this->postJson("/api/sales/invoices/{$invoice1Id}/cancel", [
            'credit_note_number' => 'DUPLICATE-CN',
            'period_id' => 2,
        ]);

        // Create second invoice
        $invoice2Response = $this->postJson('/api/sales/invoices', [
            'invoice_number' => 'API-INV-CANCEL-004',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 2000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 2000.00,
        ]);

        $invoice2Id = $invoice2Response->json('data.id');

        // Try to cancel with duplicate credit note number
        $response = $this->postJson("/api/sales/invoices/{$invoice2Id}/cancel", [
            'credit_note_number' => 'DUPLICATE-CN',
            'period_id' => 2,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['credit_note_number']);
    }
}
