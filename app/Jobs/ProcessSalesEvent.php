<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessSalesEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $payload)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Generate hash for idempotency
        $hash = sha1(json_encode($this->payload));
        
        // Check if this event has already been processed (idempotency check)
        $exists = DB::table('journal_entries')
            ->where('source_reference', $this->payload['source_reference'] ?? null)
            ->where('posting_date', $this->payload['posting_date'] ?? now())
            ->exists();
            
        if ($exists) {
            Log::info('Event already processed, skipping', ['hash' => $hash]);
            return; // Idempotent skip
        }

        DB::transaction(function () use ($hash) {
            // Create Journal Entry using Query Builder
            $journalEntryId = DB::table('journal_entries')->insertGetId([
                'entry_number' => $this->generateEntryNumber(),
                'posting_date' => $this->payload['posting_date'] ?? now(),
                'description' => $this->payload['description'] ?? 'Sales event',
                'source_reference' => $this->payload['source_reference'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Prepare lines for bulk insert
            $lines = [];
            foreach ($this->payload['lines'] as $line) {
                // Get account_id
                $account = DB::table('accounts')
                    ->where('account_code', $line['account_code'])
                    ->first(['id']);
                
                if (!$account) {
                    throw new \Exception("Account not found: {$line['account_code']}");
                }

                $lines[] = [
                    'journal_entry_id' => $journalEntryId,
                    'account_id' => $account->id,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert lines (much faster for multiple records)
            if (!empty($lines)) {
                DB::table('journal_entry_lines')->insert($lines);
            }

            Log::info('Sales event processed successfully', [
                'entry_id' => $journalEntryId,
                'hash' => $hash
            ]);
        });
    }

    /**
     * Generate unique entry number
     */
    private function generateEntryNumber(): string
    {
        $lastEntry = DB::table('journal_entries')
            ->orderByDesc('id')
            ->first(['entry_number']);
            
        $nextNumber = $lastEntry 
            ? ((int) substr($lastEntry->entry_number, 3)) + 1 
            : 1;
            
        return 'JE-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
