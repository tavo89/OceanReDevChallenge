<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Contracts\PeriodReopeningServiceInterface;
use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Models\AccountingPeriodBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodReopeningService implements PeriodReopeningServiceInterface
{
    public function __construct(
        private AccountingPeriodRepositoryInterface $periodRepository
    ) {
    }

    /**
     * Reopen a closed accounting period
     * 
     * @param string $periodCode Period code (e.g., '2025-11')
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    public function reopenPeriod(string $periodCode): array
    {
        try {
            // Find the period
            $period = $this->periodRepository->findByCode($periodCode);

            if (!$period) {
                return [
                    'success' => false,
                    'message' => "Period {$periodCode} not found.",
                    'data' => []
                ];
            }

            // Check if period can be reopened
            if (!$this->canReopenPeriod($period)) {
                return [
                    'success' => false,
                    'message' => "Period {$periodCode} cannot be reopened. Status: {$period->status}",
                    'data' => ['period' => $period]
                ];
            }

            // Reopen the period within a transaction
            DB::transaction(function () use ($period) {
                // Delete saved balances for this period
                $deletedCount = AccountingPeriodBalance::where('accounting_period_id', $period->id)->delete();
                Log::info("Deleted {$deletedCount} balance records for period {$period->period_code}");
                
                // Reopen the period
                $this->periodRepository->reopenPeriod($period);
            });

            Log::info("Period {$periodCode} reopened successfully", [
                'period_id' => $period->id,
                'status' => $period->status,
                'locked_at' => $period->locked_at
            ]);

            return [
                'success' => true,
                'message' => "Period {$periodCode} reopened successfully.",
                'data' => [
                    'period' => $period
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Error reopening period {$periodCode}: " . $e->getMessage(), [
                'exception' => $e
            ]);

            return [
                'success' => false,
                'message' => "Error reopening period: " . $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Check if a period can be reopened
     * 
     * @param AccountingPeriod $period
     * @return bool
     */
    public function canReopenPeriod(AccountingPeriod $period): bool
    {
        // Only closed periods can be reopened
        return $period->status === 'closed';
    }
}
