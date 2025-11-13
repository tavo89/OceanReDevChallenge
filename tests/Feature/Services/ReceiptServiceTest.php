<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Domain\Sales\Contracts\ReceiptServiceInterface;
use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReceiptServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReceiptServiceInterface $receiptService;
    protected PeriodClosingServiceInterface $closingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->receiptService = app(ReceiptServiceInterface::class);
        $this->closingService = app(PeriodClosingServiceInterface::class);
        
        // Seed required data
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\AccountingPeriodSeeder::class);
    }

    public function test_can_create_receipt_in_open_period(): void
    {
        $result = $this->receiptService->createReceipt([
            'receipt_number' => 'RCP-TEST-001',
            'payment_date' => '2025-11-20',
            'amount' => 500.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 500.00,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Receipt created successfully.', $result['message']);
        $this->assertNotNull($result['data']);
        $this->assertEquals('RCP-TEST-001', $result['data']->receipt_number);
    }

    public function test_cannot_create_receipt_in_closed_period(): void
    {
        // First, close period 2025-10
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);
        
        $this->closingService->closePeriod('2025-10');

        // Try to create receipt in closed period
        $result = $this->receiptService->createReceipt([
            'receipt_number' => 'RCP-TEST-002',
            'payment_date' => '2025-10-20',
            'amount' => 750.00,
            'currency' => 'USD',
            'period_id' => 1, // 2025-10 is now closed
            'exchange_rate' => 1.0,
            'base_currency_amount' => 750.00,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('closed', $result['message']);
        $this->assertStringContainsString('Only open periods allow transactions', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_cannot_create_receipt_without_period_id(): void
    {
        $result = $this->receiptService->createReceipt([
            'receipt_number' => 'RCP-TEST-003',
            'payment_date' => '2025-10-20',
            'amount' => 1000.00,
            'currency' => 'USD',
            'exchange_rate' => 1.0,
            'base_currency_amount' => 1000.00,
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Period ID is required.', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_cannot_create_receipt_with_invalid_period(): void
    {
        $result = $this->receiptService->createReceipt([
            'receipt_number' => 'RCP-TEST-004',
            'payment_date' => '2025-10-20',
            'amount' => 1500.00,
            'currency' => 'USD',
            'period_id' => 999, // Non-existent period
            'exchange_rate' => 1.0,
            'base_currency_amount' => 1500.00,
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Accounting period not found.', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_can_update_receipt_in_open_period(): void
    {
        // Create receipt first
        $createResult = $this->receiptService->createReceipt([
            'receipt_number' => 'RCP-TEST-005',
            'payment_date' => '2025-11-20',
            'amount' => 2000.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 2000.00,
        ]);

        $this->assertTrue($createResult['success']);
        $receiptId = $createResult['data']->id;

        // Update the receipt
        $updateResult = $this->receiptService->updateReceipt($receiptId, [
            'amount' => 2200.00,
            'base_currency_amount' => 2200.00,
        ]);

        $this->assertTrue($updateResult['success']);
        $this->assertEquals('Receipt updated successfully.', $updateResult['message']);
        $this->assertEquals(2200.00, $updateResult['data']->amount);
    }

    public function test_cannot_update_receipt_in_closed_period(): void
    {
        // Create receipt in open period
        $createResult = $this->receiptService->createReceipt([
            'receipt_number' => 'RCP-TEST-006',
            'payment_date' => '2025-11-20',
            'amount' => 3000.00,
            'currency' => 'USD',
            'period_id' => 2, // 2025-11 is open
            'exchange_rate' => 1.0,
            'base_currency_amount' => 3000.00,
        ]);

        $this->assertTrue($createResult['success']);
        $receiptId = $createResult['data']->id;

        // Close the period
        $this->seed(\Database\Seeders\AccountSeeder::class);
        $this->seed(\Database\Seeders\CustomerSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\JournalEntrySeeder::class);
        
        $this->closingService->closePeriod('2025-11');

        // Try to update receipt in closed period
        $updateResult = $this->receiptService->updateReceipt($receiptId, [
            'amount' => 3300.00,
            'base_currency_amount' => 3300.00,
        ]);

        $this->assertFalse($updateResult['success']);
        $this->assertStringContainsString('closed', $updateResult['message']);
        $this->assertStringContainsString('Only receipts in open periods can be modified', $updateResult['message']);
        $this->assertNull($updateResult['data']);
    }
}
