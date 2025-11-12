<?php

namespace App\Domain\Accounting\Contracts;

use App\Domain\Accounting\Models\AccountingPeriod;

interface PeriodReopeningServiceInterface
{
    /**
     * Reopen a closed accounting period
     * 
     * @param string $periodCode Period code (e.g., '2025-11')
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function reopenPeriod(string $periodCode): array;

    /**
     * Check if a period can be reopened
     * 
     * @param AccountingPeriod $period
     * @return bool
     */
    public function canReopenPeriod(AccountingPeriod $period): bool;
}
