<?php

namespace App\Repositories;

use App\Contracts\AccountingPeriodRepositoryInterface;
use App\Models\AccountingPeriod;

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
}
