<?php

namespace App\Contracts;

use App\Models\AccountingPeriod;

interface PeriodClosingServiceInterface
{
    /**
     * Close an accounting period
     *
     * @param string $periodCode
     * @return array Result with success status and message
     */
    public function closePeriod(string $periodCode): array;

    /**
     * Check if a period can be closed
     *
     * @param AccountingPeriod $period
     * @return bool
     */
    public function canClosePeriod(AccountingPeriod $period): bool;
}
