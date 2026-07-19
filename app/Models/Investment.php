<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'institution',
        'amount_invested',
        'balance',
        'rate',
        'currency',
        'type',
        'subtype',
        'purchase_date',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'balance'         => 'float',
        'amount_invested' => 'float',
        'rate'            => 'float',
        'purchase_date'   => 'date',
        'due_date'        => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getGainAttribute(): float
    {
        return $this->balance - $this->amount_invested;
    }

    public function getGainPercentAttribute(): float
    {
        if ($this->amount_invested == 0) return 0;
        return (($this->balance - $this->amount_invested) / $this->amount_invested) * 100;
    }
}
