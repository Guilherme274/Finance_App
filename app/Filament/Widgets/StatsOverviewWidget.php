<?php

namespace App\Filament\Widgets;

use App\Models\BankAccount;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class StatsOverviewWidget extends Widget
{
    protected static ?int $sort = 1;
    protected static string $view = 'filament.widgets.custom-stats-overview';
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $userId = auth()->id();
        $userAccounts = BankAccount::where('user_id', $userId)->get();
        $userAccountIds = $userAccounts->pluck('id')->toArray();

        $nubankAccounts = $userAccounts->filter(fn($acc) => 
            str_contains(strtolower($acc->name), 'nubank') ||
            str_contains(strtolower($acc->institution ?? ''), 'nubank') ||
            $acc->type === 'CREDIT'
        );
        $nubankAccountIds = $nubankAccounts->pluck('id')->toArray();

        $mpAccounts = $userAccounts->reject(fn($acc) => in_array($acc->id, $nubankAccountIds));
        $mpAccountIds = $mpAccounts->pluck('id')->toArray();

        $totalBalance = (float) $userAccounts->sum('balance');
        $totalCredit = (float) Transaction::whereIn('bank_account_id', $nubankAccountIds)->where('type', 'CREDIT')->sum('amount');
        $totalDebit = (float) Transaction::whereIn('bank_account_id', $mpAccountIds)->where('type', 'DEBIT')->sum('amount');
        $totalInvestments = (float) Investment::where('user_id', $userId)->sum('balance');

        $salary = (float) (auth()->user()?->salary ?? 0);

        $nubankMonthExpenses = (float) Transaction::whereIn('bank_account_id', $nubankAccountIds)
            ->where('type', 'CREDIT')
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('amount');

        $mpMonthExpenses = (float) Transaction::whereIn('bank_account_id', $mpAccountIds)
            ->where('type', 'DEBIT')
            ->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->sum('amount');

        $currentMonthExpenses = $nubankMonthExpenses + $mpMonthExpenses;

        // Reembolso padrão da faculdade (pago pelo pai) = R$ 1.050,00
        $reimbursedMonthExpenses = 1050.00;

        $ownMonthExpenses = max(0, $currentMonthExpenses - $reimbursedMonthExpenses);

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

        $leftover = $salary - $ownMonthExpenses - $subscriptionCost;

        $fmt = fn($val) => 'R$ ' . number_format((float)$val, 2, ',', '.');

        $cards = [
            [
                'title' => 'Saldo Total em Contas',
                'value' => $fmt($totalBalance),
                'description' => 'Disponível em contas cadastradas',
                'icon' => 'heroicon-m-building-library',
                'themeClass' => 'fin-card-emerald',
                'cardStyle' => 'background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(20, 184, 166, 0.08) 50%, rgba(16, 185, 129, 0.03) 100%); border: 1.5px solid rgba(16, 185, 129, 0.35); border-radius: 1.25rem; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.12);',
                'badgeStyle' => 'background: rgba(16, 185, 129, 0.18); color: #047857; border: 1px solid rgba(16, 185, 129, 0.35);',
                'iconStyle' => 'background: rgba(16, 185, 129, 0.22); color: #047857;',
                'breakdown' => [
                    ['label' => 'Conta Nubank:', 'value' => $fmt($nubankAccounts->sum('balance')), 'colorStyle' => 'color: #7e22ce; font-weight: 600;'],
                    ['label' => 'Conta Mercado Pago:', 'value' => $fmt($mpAccounts->sum('balance')), 'colorStyle' => 'color: #0284c7; font-weight: 600;'],
                    ['label' => '= Saldo em Contas:', 'value' => $fmt($totalBalance), 'colorStyle' => 'color: #047857; font-weight: 800; font-size: 0.875rem;'],
                ],
            ],
            [
                'title' => 'Total Crédito (Nubank)',
                'value' => $fmt($totalCredit),
                'description' => 'Fatura acumulada no cartão',
                'icon' => 'heroicon-m-credit-card',
                'themeClass' => 'fin-card-purple',
                'cardStyle' => 'background: linear-gradient(135deg, rgba(147, 51, 234, 0.15) 0%, rgba(124, 58, 237, 0.08) 50%, rgba(147, 51, 234, 0.03) 100%); border: 1.5px solid rgba(147, 51, 234, 0.35); border-radius: 1.25rem; box-shadow: 0 10px 25px -5px rgba(147, 51, 234, 0.12);',
                'badgeStyle' => 'background: rgba(147, 51, 234, 0.18); color: #6b21a8; border: 1px solid rgba(147, 51, 234, 0.35);',
                'iconStyle' => 'background: rgba(147, 51, 234, 0.22); color: #6b21a8;',
                'breakdown' => [
                    ['label' => 'Compras em Julho (Crédito):', 'value' => $fmt($totalCredit), 'colorStyle' => 'color: #7e22ce; font-weight: 600;'],
                    ['label' => '= Total Fatura Nubank:', 'value' => $fmt($totalCredit), 'colorStyle' => 'color: #6b21a8; font-weight: 800; font-size: 0.875rem;'],
                ],
            ],
            [
                'title' => 'Total Débito / Pix (Mercado Pago)',
                'value' => $fmt($totalDebit),
                'description' => 'Movimentações e Pix de saída',
                'icon' => 'heroicon-m-arrow-path',
                'themeClass' => 'fin-card-sky',
                'cardStyle' => 'background: linear-gradient(135deg, rgba(14, 165, 233, 0.15) 0%, rgba(59, 130, 246, 0.08) 50%, rgba(14, 165, 233, 0.03) 100%); border: 1.5px solid rgba(14, 165, 233, 0.35); border-radius: 1.25rem; box-shadow: 0 10px 25px -5px rgba(14, 165, 233, 0.12);',
                'badgeStyle' => 'background: rgba(14, 165, 233, 0.18); color: #0369a1; border: 1px solid rgba(14, 165, 233, 0.35);',
                'iconStyle' => 'background: rgba(14, 165, 233, 0.22); color: #0369a1;',
                'breakdown' => [
                    ['label' => 'Saídas Pix / Débito MP:', 'value' => $fmt($totalDebit), 'colorStyle' => 'color: #0284c7; font-weight: 600;'],
                    ['label' => '= Total Saídas Débito:', 'value' => $fmt($totalDebit), 'colorStyle' => 'color: #0369a1; font-weight: 800; font-size: 0.875rem;'],
                ],
            ],
            [
                'title' => 'Total Investido',
                'value' => $fmt($totalInvestments),
                'description' => 'Patrimônio em investimentos',
                'icon' => 'heroicon-m-chart-bar',
                'themeClass' => 'fin-card-amber',
                'cardStyle' => 'background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(249, 115, 22, 0.08) 50%, rgba(245, 158, 11, 0.03) 100%); border: 1.5px solid rgba(245, 158, 11, 0.35); border-radius: 1.25rem; box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.12);',
                'badgeStyle' => 'background: rgba(245, 158, 11, 0.18); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.35);',
                'iconStyle' => 'background: rgba(245, 158, 11, 0.22); color: #b45309;',
                'breakdown' => [
                    ['label' => 'Posição Ativos / Renda Fixa:', 'value' => $fmt($totalInvestments), 'colorStyle' => 'color: #d97706; font-weight: 600;'],
                    ['label' => '= Total Patrimônio Investido:', 'value' => $fmt($totalInvestments), 'colorStyle' => 'color: #b45309; font-weight: 800; font-size: 0.875rem;'],
                ],
            ],
            [
                'title' => 'Restante no Mês',
                'value' => $fmt($leftover),
                'description' => 'Saldo líquido previsto pós contas',
                'icon' => 'heroicon-m-banknotes',
                'themeClass' => $leftover >= 0 ? 'fin-card-emerald' : 'fin-card-rose',
                'cardStyle' => $leftover >= 0 
                    ? 'background: linear-gradient(135deg, rgba(16, 185, 129, 0.22) 0%, rgba(52, 211, 153, 0.12) 50%, rgba(16, 185, 129, 0.05) 100%); border: 2px solid rgba(16, 185, 129, 0.5); border-radius: 1.25rem; box-shadow: 0 12px 30px -5px rgba(16, 185, 129, 0.2);'
                    : 'background: linear-gradient(135deg, rgba(244, 63, 94, 0.22) 0%, rgba(225, 29, 72, 0.12) 50%, rgba(244, 63, 94, 0.05) 100%); border: 2px solid rgba(244, 63, 94, 0.5); border-radius: 1.25rem; box-shadow: 0 12px 30px -5px rgba(244, 63, 94, 0.2);',
                'badgeStyle' => $leftover >= 0 
                    ? 'background: rgba(16, 185, 129, 0.22); color: #047857; border: 1px solid rgba(16, 185, 129, 0.4);'
                    : 'background: rgba(244, 63, 94, 0.22); color: #be123c; border: 1px solid rgba(244, 63, 94, 0.4);',
                'iconStyle' => $leftover >= 0 
                    ? 'background: rgba(16, 185, 129, 0.28); color: #047857;'
                    : 'background: rgba(244, 63, 94, 0.28); color: #be123c;',
                'breakdown' => [
                    ['label' => '(+) Salário do próximo mês:', 'value' => $fmt($salary), 'colorStyle' => 'color: #047857; font-weight: 600;'],
                    ['label' => '(-) Fatura Nubank (Mês):', 'value' => $fmt($nubankMonthExpenses), 'colorStyle' => 'color: #e11d48; font-weight: 600;'],
                    ['label' => '(+) Reembolso Faculdade (Pai):', 'value' => $fmt($reimbursedMonthExpenses), 'colorStyle' => 'color: #047857; font-weight: 700;'],
                    ['label' => '(-) Saídas Mercado Pago (Mês):', 'value' => $fmt($mpMonthExpenses), 'colorStyle' => 'color: #e11d48; font-weight: 600;'],
                    ['label' => '(-) Custo de Assinaturas:', 'value' => $fmt($subscriptionCost), 'colorStyle' => 'color: #d97706; font-weight: 600;'],
                    ['label' => '= Saldo que irá sobrar no mês:', 'value' => $fmt($leftover), 'colorStyle' => $leftover >= 0 ? 'color: #047857; font-weight: 800; font-size: 0.875rem;' : 'color: #be123c; font-weight: 800; font-size: 0.875rem;'],
                ],
            ],
        ];

        return [
            'cards' => $cards,
            'leftover' => $leftover,
        ];
    }
}
