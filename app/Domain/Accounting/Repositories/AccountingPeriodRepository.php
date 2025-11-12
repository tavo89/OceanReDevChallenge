<?php

namespace App\Domain\Accounting\Repositories;

use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use App\Domain\Accounting\Models\AccountingPeriod;

class AccountingPeriodRepository implements AccountingPeriodRepositoryInterface
{
    /**
     * Find period by code
     *
     * @param string $code
     * @return AccountingPeriod|null
     */
    public function findByCode(string $code): ?AccountingPeriod
    {
        return AccountingPeriod::where('period_code', $code)->first();
    }

    /**
     * Update period status
     *
     * @param AccountingPeriod $period
     * @param string $status
     * @return bool
     */
    public function updateStatus(AccountingPeriod $period, string $status): bool
    {
        return $period->update(['status' => $status]);
    }

    /**
     * Lock a period
     *
     * @param AccountingPeriod $period
     * @return bool
     */
    public function lockPeriod(AccountingPeriod $period): bool
    {
        return $period->update([
            'status' => 'closed',
            'locked_at' => now()
        ]);
    }

    /**
     * Reopen a closed period
     *
     * @param AccountingPeriod $period
     * @return bool
     */
    public function reopenPeriod(AccountingPeriod $period): bool
    {
        return $period->update([
            'status' => 'open',
            'locked_at' => null
        ]);
    }
}
