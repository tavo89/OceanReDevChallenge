<?php

namespace App\Domain\Sales\Contracts;

use App\Domain\Sales\Models\Invoice;

interface InvoiceServiceInterface
{
    /**
     * Create a new invoice
     * Validates that the accounting period is open before creation
     *
     * @param array $data
     * @return array Result with success status, message, and data
     */
    public function createInvoice(array $data): array;

    /**
     * Update an existing invoice
     * Validates that the accounting period is open before update
     *
     * @param int $invoiceId
     * @param array $data
     * @return array Result with success status, message, and data
     */
    public function updateInvoice(int $invoiceId, array $data): array;
}
