<?php

namespace App\Services;

use App\Contracts\PeriodClosingServiceInterface;
use App\Contracts\AccountingPeriodRepositoryInterface;
use App\Contracts\BalanceCalculatorInterface;
use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodClosingService implements PeriodClosingServiceInterface
{
    public function __construct(
        private AccountingPeriodRepositoryInterface $periodRepository,
        private BalanceCalculatorInterface $balanceCalculator
    ) {}

    /**
     * Close an accounting period
     *
     * @param string $periodCode
     * @return array Result with success status and message
     */
    public function closePeriod(string $periodCode): array
    {
        $period = $this->periodRepository->findByCode($periodCode);

        if (!$period) {
            return [
                'success' => false,
                'message' => "Period {$periodCode} not found.",
                'data' => null
            ];
        }

        if ($period->status === 'closed') {
            return [
                'success' => false,
                'message' => "Period {$periodCode} is already closed.",
                'data' => null
            ];
        }

        if (!$this->canClosePeriod($period)) {
            return [
                'success' => false,
                'message' => "Period {$periodCode} cannot be closed at this time.",
                'data' => null
            ];
        }

        try {
            return DB::transaction(function () use ($period, $periodCode) {
                // Set status to locking
                $this->periodRepository->updateStatus($period, 'locking');
                Log::info("Period {$periodCode} status changed to 'locking'");

                // Calculate balances
                $balances = $this->balanceCalculator->calculatePeriodBalances($period->id);

                // Validate balances
                if (!$this->balanceCalculator->validateBalance($balances)) {
                    $totalDebits = $this->balanceCalculator->getTotalDebits($balances);
                    $totalCredits = $this->balanceCalculator->getTotalCredits($balances);
                    
                    throw new \Exception(
                        "Period not balanced! Total Debits: {$totalDebits}, Total Credits: {$totalCredits}"
                    );
                }

                // Lock the period
                $this->periodRepository->lockPeriod($period);
                Log::info("Period {$periodCode} closed successfully");

                return [
                    'success' => true,
                    'message' => "Period {$periodCode} closed successfully.",
                    'data' => [
                        'period' => $period->fresh(),
                        'balances' => $balances,
                        'total_debits' => $this->balanceCalculator->getTotalDebits($balances),
                        'total_credits' => $this->balanceCalculator->getTotalCredits($balances),
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error closing period {$periodCode}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => "Error closing period: " . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Check if a period can be closed
     *
     * @param AccountingPeriod $period
     * @return bool
     */
    public function canClosePeriod(AccountingPeriod $period): bool
    {
        // Add business rules for closing
        // For example: period must be open or validating
        return in_array($period->status, ['open', 'validating']);
    }
}
