<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'bank_account_id',
        'description',
        'amount',
        'date',
        'type',
        'category',
        'tags',
        'is_fixed',
        'account_name',
        'notes',
        'external_id',
    ];

    protected $casts = [
        'date'     => 'date',
        'tags'     => 'array',
        'is_fixed' => 'boolean',
        'amount'   => 'float',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
