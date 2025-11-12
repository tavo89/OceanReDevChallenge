<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'account_code',
        'name',
        'type',
        'is_postable',
        'active',
    ];

    protected $casts = [
        'is_postable' => 'boolean',
        'active' => 'boolean',
    ];
}
