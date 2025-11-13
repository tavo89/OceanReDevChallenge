<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingPeriodBalance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'accounting_period_id',
        'account_id',
        'account_code',
        'account_name',
        'account_type',
        'total_debit',
        'total_credit',
        'balance',
    ];

    protected $casts = [
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
