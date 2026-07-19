<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'institution',
        'balance',
        'currency',
        'type',
        'color',
        'notes',
    ];

    protected $casts = [
        'balance' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $typeLabel = match ($this->type) {
            'CREDIT'   => 'Cartão de Crédito',
            'DEBIT'    => 'Pix / Débito',
            'CHECKING' => 'Conta Corrente',
            'SAVINGS'  => 'Poupança',
            default    => $this->type ?? 'Geral',
        };

        return "{$this->name} ({$typeLabel})";
    }
}
