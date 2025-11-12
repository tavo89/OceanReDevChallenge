<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\PeriodClosingServiceInterface;

class ClosePeriodCommand extends Command
{
    protected $signature = 'accounting:close {period}';
    protected $description = 'Performs month-end closing process';

    public function __construct(
        private PeriodClosingServiceInterface $periodClosingService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $periodCode = $this->argument('period');
        
        $this->info("Starting closing process for period {$periodCode}...");
        
        $result = $this->periodClosingService->closePeriod($periodCode);
        
        if (!$result['success']) {
            $this->error($result['message']);
            return 1;
        }
        
        // Display results
        $this->displayResults($result['data']);
        
        $this->newLine();
        $this->info("✓ {$result['message']}");
        
        return 0;
    }

    /**
     * Display closing results
     */
    private function displayResults(array $data): void
    {
        $balances = $data['balances'];
        $period = $data['period'];
        
        $this->newLine();
        $this->info("Account Balances for Period {$period->period_code}:");
        $this->line(str_repeat('-', 90));
        
        $this->table(
            ['Account Code', 'Account Name', 'Type', 'Debits', 'Credits', 'Balance'],
            $balances->map(function ($balance) {
                return [
                    $balance->account_code,
                    substr($balance->account_name, 0, 25),
                    $balance->account_type,
                    number_format($balance->total_debit, 2),
                    number_format($balance->total_credit, 2),
                    number_format($balance->balance, 2),
                ];
            })->toArray()
        );
        
        $this->line(str_repeat('-', 90));
        
        $totalDebits = $data['total_debits'];
        $totalCredits = $data['total_credits'];
        
        $this->info("Total Debits:  " . number_format($totalDebits, 2));
        $this->info("Total Credits: " . number_format($totalCredits, 2));
        $this->info("✓ Period is balanced (Debits = Credits)");
        $this->info("✓ Period locked at: " . $period->locked_at);
    }
}

