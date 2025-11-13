<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Contracts\InvoiceServiceInterface;
use App\Domain\Sales\Contracts\InvoiceRepositoryInterface;
use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService implements InvoiceServiceInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private AccountingPeriodRepositoryInterface $periodRepository
    ) {}

    /**
     * Create a new invoice
     * Validates that the accounting period is open before creation
     *
     * @param array $data
     * @return array Result with success status, message, and data
     */
    public function createInvoice(array $data): array
    {
        // Validate required fields
        if (!isset($data['period_id'])) {
            return [
                'success' => false,
                'message' => 'Period ID is required.',
                'data' => null
            ];
        }

        // Check if period exists and is open
        $period = $this->periodRepository->find($data['period_id']);
        
        if (!$period) {
            return [
                'success' => false,
                'message' => 'Accounting period not found.',
                'data' => null
            ];
        }

        if ($period->status !== 'open') {
            return [
                'success' => false,
                'message' => "Cannot create invoice. Accounting period {$period->period_code} is {$period->status}. Only open periods allow transactions.",
                'data' => null
            ];
        }

        try {
            return DB::transaction(function () use ($data, $period) {
                $invoice = $this->invoiceRepository->create($data);
                
                Log::info("Invoice {$invoice->invoice_number} created successfully in period {$period->period_code}");
                
                return [
                    'success' => true,
                    'message' => 'Invoice created successfully.',
                    'data' => $invoice
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error creating invoice: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error creating invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Update an existing invoice
     * Validates that the accounting period is open before update
     *
     * @param int $invoiceId
     * @param array $data
     * @return array Result with success status, message, and data
     */
    public function updateInvoice(int $invoiceId, array $data): array
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);
        
        if (!$invoice) {
            return [
                'success' => false,
                'message' => 'Invoice not found.',
                'data' => null
            ];
        }

        // Determine which period to validate (new period if changing, or current period)
        $periodId = $data['period_id'] ?? $invoice->period_id;
        $period = $this->periodRepository->find($periodId);
        
        if (!$period) {
            return [
                'success' => false,
                'message' => 'Accounting period not found.',
                'data' => null
            ];
        }

        // Check if the original period is open (can't modify invoices in closed periods)
        $originalPeriod = $this->periodRepository->find($invoice->period_id);
        if ($originalPeriod && $originalPeriod->status !== 'open') {
            return [
                'success' => false,
                'message' => "Cannot update invoice. Original accounting period {$originalPeriod->period_code} is {$originalPeriod->status}. Only invoices in open periods can be modified.",
                'data' => null
            ];
        }

        // If changing period, check that the new period is also open
        if (isset($data['period_id']) && $data['period_id'] != $invoice->period_id) {
            if ($period->status !== 'open') {
                return [
                    'success' => false,
                    'message' => "Cannot move invoice to period {$period->period_code}. Target period is {$period->status}.",
                    'data' => null
                ];
            }
        }

        try {
            return DB::transaction(function () use ($invoice, $data, $period) {
                $updatedInvoice = $this->invoiceRepository->update($invoice, $data);
                
                Log::info("Invoice {$updatedInvoice->invoice_number} updated successfully");
                
                return [
                    'success' => true,
                    'message' => 'Invoice updated successfully.',
                    'data' => $updatedInvoice
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error updating invoice {$invoice->invoice_number}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error updating invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
