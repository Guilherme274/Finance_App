<?php

namespace App\Filament\Widgets;

use App\Models\BankAccount;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = auth()->id();
        $userAccountIds = BankAccount::where('user_id', $userId)->pluck('id')->toArray();

        $totalBalance = (float) BankAccount::where('user_id', $userId)->sum('balance');
        $totalCredit = (float) Transaction::whereIn('bank_account_id', $userAccountIds)->where('type', 'CREDIT')->sum('amount');
        $totalDebit = (float) Transaction::whereIn('bank_account_id', $userAccountIds)->where('type', 'DEBIT')->sum('amount');
        $totalInvestments = (float) Investment::where('user_id', $userId)->sum('balance');

        $salary = (float) auth()->user()->salary;

        $currentMonthExpenses = (float) Transaction::whereIn('bank_account_id', $userAccountIds)
            ->whereIn('type', ['DEBIT', 'CREDIT'])
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('amount');

        $activeSubscriptions = Subscription::whereIn('bank_account_id', $userAccountIds)
            ->where('active', true)
            ->get();

        $subscriptionCost = 0;
        foreach ($activeSubscriptions as $subscription) {
            if ($subscription->type === 'monthly') {
                $subscriptionCost += $subscription->amount;
            } elseif ($subscription->type === 'annual') {
                $subscriptionCost += ($subscription->amount / 12);
            }
        }

        $leftover = $salary - $currentMonthExpenses - $subscriptionCost;

        return [
            Stat::make('Saldo Total em Contas', 'R$ ' . number_format($totalBalance, 2, ',', '.'))
                ->description('Baseline das contas cadastradas')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),
            Stat::make('Total Crédito (Cartão Nubank)', 'R$ ' . number_format($totalCredit, 2, ',', '.'))
                ->description('Transações em cartão de crédito')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),
            Stat::make('Total Débito / Pix (Mercado Pago)', 'R$ ' . number_format($totalDebit, 2, ',', '.'))
                ->description('Transações em Pix / Débito')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('danger'),
            Stat::make('Total Investido', 'R$ ' . number_format($totalInvestments, 2, ',', '.'))
                ->description('Patrimônio em ativos')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
            Stat::make('Restante no Mês', 'R$ ' . number_format($leftover, 2, ',', '.'))
                ->description('Salário - (Despesas do Mês + Assinaturas)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($leftover >= 0 ? 'success' : 'danger'),
        ];
    }
}
