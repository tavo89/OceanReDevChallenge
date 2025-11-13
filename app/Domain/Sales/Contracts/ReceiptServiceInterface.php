<?php

namespace App\Domain\Sales\Contracts;

use App\Domain\Sales\Models\Receipt;

interface ReceiptServiceInterface
{
    /**
     * Create a new receipt
     * Validates that the accounting period is open before creation
     *
     * @param array $data
     * @return array Result with success status, message, and data
     */
    public function createReceipt(array $data): array;

    /**
     * Update an existing receipt
     * Validates that the accounting period is open before update
     *
     * @param int $receiptId
     * @param array $data
     * @return array Result with success status, message, and data
     */
    public function updateReceipt(int $receiptId, array $data): array;
}
