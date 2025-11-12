<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'accounting_period_id',
        'entry_date',
        'reference',
        'description',
        'source_type',
        'source_reference',
        'currency_code',
        'exchange_rate',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exchange_rate' => 'decimal:6',
    ];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }
}
