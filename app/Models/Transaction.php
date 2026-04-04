<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'bank_account_id',
        'pluggy_transaction_id',
        'description',
        'amount',
        'date',
        'status',
        'type',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
