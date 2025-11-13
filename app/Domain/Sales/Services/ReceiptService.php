<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Contracts\ReceiptServiceInterface;
use App\Domain\Sales\Contracts\ReceiptRepositoryInterface;
use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceiptService implements ReceiptServiceInterface
{
    public function __construct(
        private ReceiptRepositoryInterface $receiptRepository,
        private AccountingPeriodRepositoryInterface $periodRepository
    ) {}

    /**
     * Create a new receipt
     * Validates that the accounting period is open before creation
     *
     * @param array $data
     * @return array Result with success status, message, and data
     */
    public function createReceipt(array $data): array
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
                'message' => "Cannot create receipt. Accounting period {$period->period_code} is {$period->status}. Only open periods allow transactions.",
                'data' => null
            ];
        }

        try {
            return DB::transaction(function () use ($data, $period) {
                $receipt = $this->receiptRepository->create($data);
                
                Log::info("Receipt {$receipt->receipt_number} created successfully in period {$period->period_code}");
                
                return [
                    'success' => true,
                    'message' => 'Receipt created successfully.',
                    'data' => $receipt
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error creating receipt: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error creating receipt: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Update an existing receipt
     * Validates that the accounting period is open before update
     *
     * @param int $receiptId
     * @param array $data
     * @return array Result with success status, message, and data
     */
    public function updateReceipt(int $receiptId, array $data): array
    {
        $receipt = $this->receiptRepository->findById($receiptId);
        
        if (!$receipt) {
            return [
                'success' => false,
                'message' => 'Receipt not found.',
                'data' => null
            ];
        }

        // Determine which period to validate (new period if changing, or current period)
        $periodId = $data['period_id'] ?? $receipt->period_id;
        $period = $this->periodRepository->find($periodId);
        
        if (!$period) {
            return [
                'success' => false,
                'message' => 'Accounting period not found.',
                'data' => null
            ];
        }

        // Check if the original period is open (can't modify receipts in closed periods)
        $originalPeriod = $this->periodRepository->find($receipt->period_id);
        if ($originalPeriod && $originalPeriod->status !== 'open') {
            return [
                'success' => false,
                'message' => "Cannot update receipt. Original accounting period {$originalPeriod->period_code} is {$originalPeriod->status}. Only receipts in open periods can be modified.",
                'data' => null
            ];
        }

        // If changing period, check that the new period is also open
        if (isset($data['period_id']) && $data['period_id'] != $receipt->period_id) {
            if ($period->status !== 'open') {
                return [
                    'success' => false,
                    'message' => "Cannot move receipt to period {$period->period_code}. Target period is {$period->status}.",
                    'data' => null
                ];
            }
        }

        try {
            return DB::transaction(function () use ($receipt, $data, $period) {
                $updatedReceipt = $this->receiptRepository->update($receipt, $data);
                
                Log::info("Receipt {$updatedReceipt->receipt_number} updated successfully");
                
                return [
                    'success' => true,
                    'message' => 'Receipt updated successfully.',
                    'data' => $updatedReceipt
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error updating receipt {$receipt->receipt_number}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error updating receipt: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
