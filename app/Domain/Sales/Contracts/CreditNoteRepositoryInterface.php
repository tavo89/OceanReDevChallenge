<?php

namespace App\Domain\Sales\Contracts;

use App\Domain\Sales\Models\CreditNote;

interface CreditNoteRepositoryInterface
{
    /**
     * Create a new credit note
     *
     * @param array $data
     * @return CreditNote
     */
    public function create(array $data): CreditNote;

    /**
     * Find a credit note by ID
     *
     * @param int $id
     * @return CreditNote|null
     */
    public function findById(int $id): ?CreditNote;

    /**
     * Find a credit note by invoice ID
     *
     * @param int $invoiceId
     * @return CreditNote|null
     */
    public function findByInvoiceId(int $invoiceId): ?CreditNote;
}
