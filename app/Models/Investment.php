<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    protected $fillable = [
        'user_id',
        'pluggy_investment_id',
        'pluggy_item_id',
        'name',
        'balance',
        'currency',
        'type',
        'subtype',
        'number',
        'metadata',
    ];

    protected $casts = [
        'balance' => 'float',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
