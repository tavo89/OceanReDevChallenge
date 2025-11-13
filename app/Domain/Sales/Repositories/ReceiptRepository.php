<?php

namespace App\Domain\Sales\Repositories;

use App\Domain\Sales\Contracts\ReceiptRepositoryInterface;
use App\Domain\Sales\Models\Receipt;

class ReceiptRepository implements ReceiptRepositoryInterface
{
    /**
     * Create a new receipt
     *
     * @param array $data
     * @return Receipt
     */
    public function create(array $data): Receipt
    {
        return Receipt::create($data);
    }

    /**
     * Update an existing receipt
     *
     * @param Receipt $receipt
     * @param array $data
     * @return Receipt
     */
    public function update(Receipt $receipt, array $data): Receipt
    {
        $receipt->update($data);
        return $receipt->fresh();
    }

    /**
     * Find a receipt by ID
     *
     * @param int $id
     * @return Receipt|null
     */
    public function findById(int $id): ?Receipt
    {
        return Receipt::find($id);
    }

    /**
     * Find a receipt by receipt number
     *
     * @param string $receiptNumber
     * @return Receipt|null
     */
    public function findByNumber(string $receiptNumber): ?Receipt
    {
        return Receipt::where('receipt_number', $receiptNumber)->first();
    }
}
