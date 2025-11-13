<?php

namespace App\Domain\Sales\Services;

use App\Domain\Sales\Contracts\InvoiceCancellationServiceInterface;
use App\Domain\Sales\Contracts\InvoiceRepositoryInterface;
use App\Domain\Sales\Contracts\CreditNoteRepositoryInterface;
use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceCancellationService implements InvoiceCancellationServiceInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private CreditNoteRepositoryInterface $creditNoteRepository,
        private AccountingPeriodRepositoryInterface $periodRepository
    ) {}

    /**
     * Cancel an invoice and create a credit note
     * Invoices can be cancelled even if the period is closed
     * But the credit note must be created in an open period
     *
     * @param int $invoiceId
     * @param array $creditNoteData Data for the credit note (period_id, issue_date, reason, etc.)
     * @return array Result with success status, message, and data
     */
    public function cancelInvoice(int $invoiceId, array $creditNoteData): array
    {
        // Find the invoice
        $invoice = $this->invoiceRepository->findById($invoiceId);
        
        if (!$invoice) {
            return [
                'success' => false,
                'message' => 'Invoice not found.',
                'data' => null
            ];
        }

        // Check if invoice is already cancelled
        if ($invoice->status === 'cancelled') {
            return [
                'success' => false,
                'message' => "Invoice {$invoice->invoice_number} is already cancelled.",
                'data' => null
            ];
        }

        // Check if credit note already exists
        $existingCreditNote = $this->creditNoteRepository->findByInvoiceId($invoiceId);
        if ($existingCreditNote) {
            return [
                'success' => false,
                'message' => "Credit note already exists for invoice {$invoice->invoice_number}.",
                'data' => null
            ];
        }

        // Validate credit note period_id is provided
        if (!isset($creditNoteData['period_id'])) {
            return [
                'success' => false,
                'message' => 'Credit note period_id is required.',
                'data' => null
            ];
        }

        // Check if the credit note period exists and is open
        $creditNotePeriod = $this->periodRepository->find($creditNoteData['period_id']);
        
        if (!$creditNotePeriod) {
            return [
                'success' => false,
                'message' => 'Credit note accounting period not found.',
                'data' => null
            ];
        }

        // BUSINESS RULE: Credit note can only be created in open periods
        // Even if the invoice's original period is closed, cancellation is allowed
        if ($creditNotePeriod->status !== 'open') {
            return [
                'success' => false,
                'message' => "Cannot create credit note. Accounting period {$creditNotePeriod->period_code} is {$creditNotePeriod->status}. Credit notes can only be created in open periods.",
                'data' => null
            ];
        }

        try {
            return DB::transaction(function () use ($invoice, $creditNoteData, $creditNotePeriod) {
                // Cancel the invoice
                $invoice->status = 'cancelled';
                $invoice->cancelled_at = now();
                $invoice->save();

                // Create the credit note with invoice amount
                $creditNote = $this->creditNoteRepository->create([
                    'credit_note_number' => $creditNoteData['credit_note_number'],
                    'invoice_id' => $invoice->id,
                    'issue_date' => $creditNoteData['issue_date'] ?? now()->format('Y-m-d'),
                    'amount' => $invoice->total_amount,
                    'currency' => $invoice->currency,
                    'period_id' => $creditNoteData['period_id'],
                    'exchange_rate' => $invoice->exchange_rate,
                    'base_currency_amount' => $invoice->base_currency_amount,
                    'reason' => $creditNoteData['reason'] ?? 'Invoice cancellation',
                ]);

                Log::info("Invoice {$invoice->invoice_number} cancelled. Credit note {$creditNote->credit_note_number} created in period {$creditNotePeriod->period_code}");

                return [
                    'success' => true,
                    'message' => 'Invoice cancelled successfully.',
                    'data' => [
                        'invoice' => $invoice->fresh(),
                        'credit_note' => $creditNote,
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error cancelling invoice {$invoice->invoice_number}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error cancelling invoice: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
