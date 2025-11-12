<?php

namespace App\Domain\Accounting\Contracts;

use Illuminate\Support\Collection;

interface BalanceCalculatorInterface
{
    /**
     * Calculate account balances for a specific period
     *
     * @param int $periodId
     * @return Collection
     */
    public function calculatePeriodBalances(int $periodId): Collection;

    /**
     * Validate that debits equal credits
     *
     * @param Collection $balances
     * @return bool
     */
    public function validateBalance(Collection $balances): bool;

    /**
     * Get total debits from balances
     *
     * @param Collection $balances
     * @return float
     */
    public function getTotalDebits(Collection $balances): float;

    /**
     * Get total credits from balances
     *
     * @param Collection $balances
     * @return float
     */
    public function getTotalCredits(Collection $balances): float;
}
