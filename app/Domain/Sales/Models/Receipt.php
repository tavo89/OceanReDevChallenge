<?php

namespace App\Domain\Sales\Models;

use App\Domain\Accounting\Models\AccountingPeriod;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'payment_date',
        'amount',
        'currency',
        'period_id',
        'exchange_rate',
        'base_currency_amount',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'base_currency_amount' => 'decimal:2',
    ];

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }
}
