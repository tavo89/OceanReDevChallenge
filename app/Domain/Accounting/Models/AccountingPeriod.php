<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    protected $fillable = [
        'period_code',
        'status',
        'locked_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];
}
