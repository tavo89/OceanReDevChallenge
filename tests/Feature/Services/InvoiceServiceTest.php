<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Domain\Sales\Contracts\InvoiceServiceInterface;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceServiceInterface $invoiceService;
    protected PeriodClosingServiceInterface $closingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->invoiceService = app(InvoiceServiceInterface::class);
        $this->closingService = app(PeriodClosingServiceInterface::class);
        
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
    }

    public function test_can_create_invoice_in_open_period(): void
    {
        $result = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-TEST-001',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 1000.00,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Invoice created successfully.', $result['message']);
        $this->assertNotNull($result['data']);
        $this->assertEquals('INV-TEST-001', $result['data']->invoice_number);
    }

    public function test_cannot_create_invoice_in_closed_period(): void
    {
        // First, close period 2025-10
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);
        
        $this->closingService->closePeriod('2025-10');

        // Try to create invoice in closed period
        $result = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-TEST-002',
            'issue_date' => '2025-10-15',
            'due_date' => '2025-11-15',
            'customer_id' => 1,
            'total_amount' => 2000.00,
            'currency' => 'USD',
            'period_id' => 1, // 2025-10 is now closed
            'exchange_rate' => 1.0,
            'base_currency_amount' => 2000.00,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('closed', $result['message']);
        $this->assertStringContainsString('Only open periods allow transactions', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_cannot_create_invoice_without_period_id(): void
    {
        $result = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-TEST-003',
            'issue_date' => '2025-10-15',
            'due_date' => '2025-11-15',
            'customer_id' => 1,
            'total_amount' => 3000.00,
            'currency' => 'USD',
            'exchange_rate' => 1.0,
            'base_currency_amount' => 3000.00,
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Period ID is required.', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_cannot_create_invoice_with_invalid_period(): void
    {
        $result = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-TEST-004',
            'issue_date' => '2025-10-15',
            'due_date' => '2025-11-15',
            'customer_id' => 1,
            'total_amount' => 4000.00,
            'currency' => 'USD',
            'period_id' => 999, // Non-existent period
            'exchange_rate' => 1.0,
            'base_currency_amount' => 4000.00,
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Accounting period not found.', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_can_update_invoice_in_open_period(): void
    {
        // Create invoice first
        $createResult = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-TEST-005',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 5000.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 5000.00,
        ]);

        $this->assertTrue($createResult['success']);
        $invoiceId = $createResult['data']->id;

        // Update the invoice
        $updateResult = $this->invoiceService->updateInvoice($invoiceId, [
            'total_amount' => 5500.00,
            'base_currency_amount' => 5500.00,
        ]);

        $this->assertTrue($updateResult['success']);
        $this->assertEquals('Invoice updated successfully.', $updateResult['message']);
        $this->assertEquals(5500.00, $updateResult['data']->total_amount);
    }

    public function test_cannot_update_invoice_in_closed_period(): void
    {
        // Create invoice in open period
        $createResult = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-TEST-006',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 6000.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 6000.00,
        ]);

        $this->assertTrue($createResult['success']);
        $invoiceId = $createResult['data']->id;

        // Close the period
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);
        
        $this->closingService->closePeriod('2025-11');

        // Try to update invoice in closed period
        $updateResult = $this->invoiceService->updateInvoice($invoiceId, [
            'total_amount' => 6500.00,
            'base_currency_amount' => 6500.00,
        ]);

        $this->assertFalse($updateResult['success']);
        $this->assertStringContainsString('closed', $updateResult['message']);
        $this->assertStringContainsString('Only invoices in open periods can be modified', $updateResult['message']);
        $this->assertNull($updateResult['data']);
    }
}
