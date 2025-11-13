<?php

namespace App\Domain\Sales\Repositories;

use App\Domain\Sales\Contracts\InvoiceRepositoryInterface;
use App\Domain\Sales\Models\Invoice;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * Create a new invoice
     *
     * @param array $data
     * @return Invoice
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * Update an existing invoice
     *
     * @param Invoice $invoice
     * @param array $data
     * @return Invoice
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice->fresh();
    }

    /**
     * Find an invoice by ID
     *
     * @param int $id
     * @return Invoice|null
     */
    public function findById(int $id): ?Invoice
    {
        return Invoice::find($id);
    }

    /**
     * Find an invoice by invoice number
     *
     * @param string $invoiceNumber
     * @return Invoice|null
     */
    public function findByNumber(string $invoiceNumber): ?Invoice
    {
        return Invoice::where('invoice_number', $invoiceNumber)->first();
    }
}
