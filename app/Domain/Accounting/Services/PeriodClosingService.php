<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Contracts\PeriodClosingServiceInterface;
use App\Domain\Accounting\Contracts\AccountingPeriodRepositoryInterface;
use App\Domain\Accounting\Contracts\BalanceCalculatorInterface;
use App\Domain\Accounting\Models\AccountingPeriod;
use App\Domain\Accounting\Models\AccountingPeriodBalance;
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

                // Save balances to database
                $this->saveBalances($period->id, $balances);
                Log::info("Period {$periodCode} balances saved to database");

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

    /**
     * Save balances to the accounting_period_balances table
     *
     * @param int $periodId
     * @param \Illuminate\Support\Collection $balances Collection of stdClass objects with balance data
     * @return void
     */
    private function saveBalances(int $periodId, $balances): void
    {
        // Soft delete existing balances for this period (preserves audit history)
        AccountingPeriodBalance::where('accounting_period_id', $periodId)->delete();

        // Insert new balance records
        foreach ($balances as $balance) {
            /** @var \stdClass $balance */
            AccountingPeriodBalance::create([
                'accounting_period_id' => $periodId,
                'account_id' => $balance->account_id,
                'account_code' => $balance->account_code,
                'account_name' => $balance->account_name,
                'account_type' => $balance->account_type,
                'total_debit' => $balance->total_debit,
                'total_credit' => $balance->total_credit,
                'balance' => $balance->total_debit - $balance->total_credit,
            ]);
        }
    }
}
