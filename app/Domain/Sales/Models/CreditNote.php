<?php

namespace App\Domain\Sales\Models;

use App\Domain\Accounting\Models\AccountingPeriod;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'credit_note_number',
        'invoice_id',
        'issue_date',
        'amount',
        'currency',
        'period_id',
        'exchange_rate',
        'base_currency_amount',
        'reason',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'base_currency_amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }
}
