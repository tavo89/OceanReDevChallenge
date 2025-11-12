<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Accounting\Contracts\BalanceCalculatorInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BalanceCalculatorService implements BalanceCalculatorInterface
{
    /**
     * Calculate account balances for a specific period
     *
     * @param int $periodId
     * @return Collection
     */
    public function calculatePeriodBalances(int $periodId): Collection
    {
        // Get all journal entries related to this period through invoices and receipts
        $balances = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where(function ($query) use ($periodId) {
                // Join with invoices for this period
                $query->whereExists(function ($subQuery) use ($periodId) {
                    $subQuery->select(DB::raw(1))
                        ->from('invoices')
                        ->whereRaw("journal_entries.source_reference = CONCAT('invoice:', invoices.invoice_number)")
                        ->where('invoices.period_id', '=', $periodId);
                })
                // OR join with receipts for this period
                ->orWhereExists(function ($subQuery) use ($periodId) {
                    $subQuery->select(DB::raw(1))
                        ->from('receipts')
                        ->whereRaw("journal_entries.source_reference = CONCAT('receipt:', receipts.receipt_number)")
                        ->where('receipts.period_id', '=', $periodId);
                });
            })
            ->select(
                'accounts.account_code',
                'accounts.name as account_name',
                'accounts.type as account_type',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
                DB::raw('SUM(journal_entry_lines.debit - journal_entry_lines.credit) as balance')
            )
            ->groupBy('accounts.account_code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.account_code')
            ->get();

        return $balances;
    }

    /**
     * Validate that debits equal credits
     *
     * @param Collection $balances
     * @return bool
     */
    public function validateBalance(Collection $balances): bool
    {
        $totalDebits = $balances->sum('total_debit');
        $totalCredits = $balances->sum('total_credit');

        // Allow for small rounding differences (1 cent)
        return abs($totalDebits - $totalCredits) < 0.01;
    }

    /**
     * Get total debits from balances
     *
     * @param Collection $balances
     * @return float
     */
    public function getTotalDebits(Collection $balances): float
    {
        return (float) $balances->sum('total_debit');
    }

    /**
     * Get total credits from balances
     *
     * @param Collection $balances
     * @return float
     */
    public function getTotalCredits(Collection $balances): float
    {
        return (float) $balances->sum('total_credit');
    }
}
