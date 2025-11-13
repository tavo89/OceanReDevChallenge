<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Domain\Sales\Contracts\InvoiceCancellationServiceInterface;
use App\Domain\Sales\Contracts\InvoiceServiceInterface;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceCancellationServiceInterface $cancellationService;
    protected InvoiceServiceInterface $invoiceService;
    protected PeriodClosingServiceInterface $closingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cancellationService = app(InvoiceCancellationServiceInterface::class);
        $this->invoiceService = app(InvoiceServiceInterface::class);
        $this->closingService = app(PeriodClosingServiceInterface::class);
        
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
    }

    public function test_can_cancel_invoice_in_open_period(): void
    {
        // Create invoice
        $invoiceResult = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-CANCEL-001',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 1000.00,
        ]);

        $this->assertTrue($invoiceResult['success']);
        $invoiceId = $invoiceResult['data']->id;

        // Cancel invoice
        $result = $this->cancellationService->cancelInvoice($invoiceId, [
            'credit_note_number' => 'CN-001',
            'period_id' => 2, // Create credit note in open period
            'issue_date' => '2025-11-20',
            'reason' => 'Customer request',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Invoice cancelled successfully.', $result['message']);
        $this->assertNotNull($result['data']['invoice']);
        $this->assertNotNull($result['data']['credit_note']);
        $this->assertEquals('cancelled', $result['data']['invoice']->status);
        $this->assertNotNull($result['data']['invoice']->cancelled_at);
        $this->assertEquals($invoiceId, $result['data']['credit_note']->invoice_id);
        $this->assertEquals(1000.00, $result['data']['credit_note']->amount);
    }

    public function test_can_cancel_invoice_from_closed_period_with_credit_note_in_open_period(): void
    {
        // Create invoice in period that will be closed
        $invoiceResult = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-CANCEL-002',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 2000.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11
            'exchange_rate' => 1.0,
            'base_currency_amount' => 2000.00,
        ]);

        $this->assertTrue($invoiceResult['success']);
        $invoiceId = $invoiceResult['data']->id;

        // Close the period
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);
        $this->closingService->closePeriod('2025-11');

        // Cancel invoice even though its period is closed, but credit note in open period (2025-12)
        $result = $this->cancellationService->cancelInvoice($invoiceId, [
            'credit_note_number' => 'CN-002',
            'period_id' => 3, // 2025-12 is open
            'issue_date' => '2025-12-01',
            'reason' => 'Invoice from closed period',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('cancelled', $result['data']['invoice']->status);
        $this->assertEquals(3, $result['data']['credit_note']->period_id);
    }

    public function test_cannot_create_credit_note_in_closed_period(): void
    {
        // Create invoice in open period
        $invoiceResult = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-CANCEL-003',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 3000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 3000.00,
        ]);

        $invoiceId = $invoiceResult['data']->id;

        // Try to create credit note in closed period
        $result = $this->cancellationService->cancelInvoice($invoiceId, [
            'credit_note_number' => 'CN-003',
            'period_id' => 1, // 2025-10 is closed
            'issue_date' => '2025-10-20',
            'reason' => 'Testing closed period',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Credit notes can only be created in open periods', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_cannot_cancel_already_cancelled_invoice(): void
    {
        // Create and cancel invoice
        $invoiceResult = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-CANCEL-004',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 4000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 4000.00,
        ]);

        $invoiceId = $invoiceResult['data']->id;

        $this->cancellationService->cancelInvoice($invoiceId, [
            'credit_note_number' => 'CN-004',
            'period_id' => 2,
            'issue_date' => '2025-11-20',
            'reason' => 'First cancellation',
        ]);

        // Try to cancel again
        $result = $this->cancellationService->cancelInvoice($invoiceId, [
            'credit_note_number' => 'CN-004-DUPLICATE',
            'period_id' => 2,
            'issue_date' => '2025-11-21',
            'reason' => 'Second cancellation',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already cancelled', $result['message']);
    }

    public function test_cannot_cancel_nonexistent_invoice(): void
    {
        $result = $this->cancellationService->cancelInvoice(99999, [
            'credit_note_number' => 'CN-005',
            'period_id' => 2,
            'issue_date' => '2025-11-20',
            'reason' => 'Testing',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invoice not found.', $result['message']);
    }

    public function test_requires_period_id_for_credit_note(): void
    {
        $invoiceResult = $this->invoiceService->createInvoice([
            'invoice_number' => 'INV-CANCEL-006',
            'issue_date' => '2025-11-15',
            'due_date' => '2025-12-15',
            'customer_id' => 1,
            'total_amount' => 5000.00,
            'currency' => 'USD',
            'period_id' => 2,
            'exchange_rate' => 1.0,
            'base_currency_amount' => 5000.00,
        ]);

        $invoiceId = $invoiceResult['data']->id;

        $result = $this->cancellationService->cancelInvoice($invoiceId, [
            'credit_note_number' => 'CN-006',
            // Missing period_id
            'issue_date' => '2025-11-20',
            'reason' => 'Testing',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Credit note period_id is required.', $result['message']);
    }
}
