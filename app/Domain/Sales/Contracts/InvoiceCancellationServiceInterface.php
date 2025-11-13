<?php

namespace App\Domain\Sales\Contracts;

interface InvoiceCancellationServiceInterface
{
    /**
     * Cancel an invoice and create a credit note
     * Invoices can be cancelled even if the period is closed
     * But the credit note must be created in an open period
     *
     * @param int $invoiceId
     * @param array $creditNoteData Data for the credit note (period_id, issue_date, reason, etc.)
     * @return array Result with success status, message, and data
     */
    public function cancelInvoice(int $invoiceId, array $creditNoteData): array;
}
