<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntryLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'line_number',
        'description',
        'debit',
        'credit',
        'currency_code',
        'exchange_rate',
        'debit_local',
        'credit_local',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'debit_local' => 'decimal:2',
        'credit_local' => 'decimal:2',
        'line_number' => 'integer',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
