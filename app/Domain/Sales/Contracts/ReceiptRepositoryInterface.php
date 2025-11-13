<?php

namespace App\Domain\Sales\Contracts;

use App\Domain\Sales\Models\Receipt;

interface ReceiptRepositoryInterface
{
    /**
     * Create a new receipt
     *
     * @param array $data
     * @return Receipt
     */
    public function create(array $data): Receipt;

    /**
     * Update an existing receipt
     *
     * @param Receipt $receipt
     * @param array $data
     * @return Receipt
     */
    public function update(Receipt $receipt, array $data): Receipt;

    /**
     * Find a receipt by ID
     *
     * @param int $id
     * @return Receipt|null
     */
    public function findById(int $id): ?Receipt;

    /**
     * Find a receipt by receipt number
     *
     * @param string $receiptNumber
     * @return Receipt|null
     */
    public function findByNumber(string $receiptNumber): ?Receipt;
}
