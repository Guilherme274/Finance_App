<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'name',
        'amount',
        'type',
        'start_date',
        'category',
        'active',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'start_date' => 'date',
        'active' => 'boolean',
    ];

    protected $appends = [
        'remaining_months',
        'current_installment',
        'projected_yearly_cost',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function getRemainingMonthsAttribute()
    {
        if ($this->type !== 'annual' || !$this->start_date) {
            return null;
        }

        $start = \Carbon\Carbon::parse($this->start_date);
        $now = \Carbon\Carbon::now();
        
        $monthsPassed = $start->diffInMonths($now);
        // Annual subscriptions renew every 12 months.
        $monthsPassedInCurrentCycle = $monthsPassed % 12;
        $remaining = 12 - $monthsPassedInCurrentCycle;
        
        return $remaining;
    }

    public function getCurrentInstallmentAttribute()
    {
        if ($this->type !== 'annual' || !$this->start_date) {
            return null;
        }

        $remaining = $this->remaining_months;
        return 12 - $remaining + 1; // e.g. if 9 remaining, we are on month 4
    }

    public function getProjectedYearlyCostAttribute()
    {
        return $this->type === 'monthly' ? $this->amount * 12 : $this->amount;
    }
}
