<?php

namespace App\Contracts;

use App\Models\AccountingPeriod;

interface AccountingPeriodRepositoryInterface
{
    /**
     * Find period by code
     *
     * @param string $code
     * @return AccountingPeriod|null
     */
    public function findByCode(string $code): ?AccountingPeriod;

    /**
     * Update period status
     *
     * @param AccountingPeriod $period
     * @param string $status
     * @return bool
     */
    public function updateStatus(AccountingPeriod $period, string $status): bool;

    /**
     * Lock a period
     *
     * @param AccountingPeriod $period
     * @return bool
     */
    public function lockPeriod(AccountingPeriod $period): bool;
}
