<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Accounting\Contracts\PeriodReopeningServiceInterface;

class ReopenPeriodCommand extends Command
{
    protected $signature = 'accounting:reopen {period}';
    protected $description = 'Reopens a closed accounting period';

    public function __construct(
        private PeriodReopeningServiceInterface $periodReopeningService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $periodCode = $this->argument('period');
        
        $this->info("Starting reopening process for period {$periodCode}...");
        
        // Confirm action
        if (!$this->confirm("Are you sure you want to reopen period {$periodCode}? This will allow modifications to closed transactions.")) {
            $this->warn('Operation cancelled.');
            return 0;
        }
        
        $result = $this->periodReopeningService->reopenPeriod($periodCode);
        
        if (!$result['success']) {
            $this->error($result['message']);
            return 1;
        }
        
        // Display results
        $period = $result['data']['period'];
        
        $this->newLine();
        $this->info("✓ {$result['message']}");
        $this->line("Period Code: {$period->period_code}");
        $this->line("Status: {$period->status}");
        $this->line("Locked At: " . ($period->locked_at ? $period->locked_at->format('Y-m-d H:i:s') : 'Not locked'));
        
        return 0;
    }
}
