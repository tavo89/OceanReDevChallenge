<?php

namespace App\Domain\Sales\Repositories;

use App\Domain\Sales\Contracts\CreditNoteRepositoryInterface;
use App\Domain\Sales\Models\CreditNote;

class CreditNoteRepository implements CreditNoteRepositoryInterface
{
    /**
     * Create a new credit note
     *
     * @param array $data
     * @return CreditNote
     */
    public function create(array $data): CreditNote
    {
        return CreditNote::create($data);
    }

    /**
     * Find a credit note by ID
     *
     * @param int $id
     * @return CreditNote|null
     */
    public function findById(int $id): ?CreditNote
    {
        return CreditNote::find($id);
    }

    /**
     * Find a credit note by invoice ID
     *
     * @param int $invoiceId
     * @return CreditNote|null
     */
    public function findByInvoiceId(int $invoiceId): ?CreditNote
    {
        return CreditNote::where('invoice_id', $invoiceId)->first();
    }
}
