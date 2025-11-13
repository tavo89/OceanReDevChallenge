<?php

namespace App\Domain\Sales\Contracts;

use App\Domain\Sales\Models\Invoice;

interface InvoiceRepositoryInterface
{
    /**
     * Create a new invoice
     *
     * @param array $data
     * @return Invoice
     */
    public function create(array $data): Invoice;

    /**
     * Update an existing invoice
     *
     * @param Invoice $invoice
     * @param array $data
     * @return Invoice
     */
    public function update(Invoice $invoice, array $data): Invoice;

    /**
     * Find an invoice by ID
     *
     * @param int $id
     * @return Invoice|null
     */
    public function findById(int $id): ?Invoice;

    /**
     * Find an invoice by invoice number
     *
     * @param string $invoiceNumber
     * @return Invoice|null
     */
    public function findByNumber(string $invoiceNumber): ?Invoice;
}
