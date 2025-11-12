<?php

namespace App\Domain\Sales\Models;

use App\Domain\Accounting\Models\AccountingPeriod;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'issue_date',
        'due_date',
        'customer_id',
        'total_amount',
        'currency',
        'period_id',
        'exchange_rate',
        'base_currency_amount',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'base_currency_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }
}
