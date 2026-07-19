<?php

namespace App\Filament\Pages;

use App\Models\Investment;
use Filament\Pages\Page;

class InvestmentsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $navigationLabel = 'Hub de Investimentos';
    protected static ?string $title = 'Central de Investimentos & Recomendações';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.investments-dashboard';

    public function getViewData(): array
    {
        $userId = auth()->id();
        $myInvestments = Investment::where('user_id', $userId)->get();

        $totalInvested = (float) $myInvestments->sum('amount_invested');
        $totalBalance = (float) $myInvestments->sum('balance');
        $totalProfit = $totalBalance - $totalInvested;
        $profitPercentage = $totalInvested > 0 ? ($totalProfit / $totalInvested) * 100 : 0;

        $typeBreakdown = $myInvestments->groupBy('type')->map(function ($items, $type) {
            return [
                'type' => $type,
                'count' => $items->count(),
                'balance' => $items->sum('balance'),
            ];
        });

        // Recommended market assets
        $recommendations = [
            [
                'name' => 'CDB 100% CDI Liquidez Diária',
                'category' => 'Renda Fixa',
                'institution' => 'Banco Sofisa / C6 Bank',
                'yield' => '10.5% a.a.',
                'risk' => 'Muito Baixo',
                'minInvestment' => 'R$ 1,00',
                'description' => 'Ideal para reserva de emergência. Liquidez imediata e garantia do FGC até R$ 250 mil.',
                'icon' => 'heroicon-m-shield-check',
                'colorClass' => 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20',
            ],
            [
                'name' => 'Tesouro Selic 2029',
                'category' => 'Tesouro Direto',
                'institution' => 'Tesouro Nacional',
                'yield' => 'Selic + 0,15% a.a.',
                'risk' => 'Muito Baixo',
                'minInvestment' => 'R$ 150,00',
                'description' => 'Título mais seguro do Brasil. Rendimento diário atrelado à taxa Selic com garantia do Governo.',
                'icon' => 'heroicon-m-building-library',
                'colorClass' => 'text-indigo-500 bg-indigo-500/10 border-indigo-500/20',
            ],
            [
                'name' => 'HGLG11 - CSHG Logística',
                'category' => 'Fundos Imobiliários',
                'institution' => 'B3 / Credit Suisse',
                'yield' => '9.2% a.a. (Isento de IR)',
                'risk' => 'Médio',
                'minInvestment' => 'R$ 160,00',
                'description' => 'Maior FII de galpões logísticos do Brasil. Dividendos mensais pingando direto na conta.',
                'icon' => 'heroicon-m-building-office-2',
                'colorClass' => 'text-sky-500 bg-sky-500/10 border-sky-500/20',
            ],
            [
                'name' => 'IVVB11 - S&P 500 ETF',
                'category' => 'Internacional / ETF',
                'institution' => 'BlackRock / B3',
                'yield' => 'Histórico ~14% a.a.',
                'risk' => 'Médio/Alto',
                'minInvestment' => 'R$ 310,00',
                'description' => 'Invista nas 500 maiores empresas americanas (Apple, Microsoft, Nvidia, Amazon) dolarizando o patrimônio.',
                'icon' => 'heroicon-m-globe-americas',
                'colorClass' => 'text-purple-500 bg-purple-500/10 border-purple-500/20',
            ],
            [
                'name' => 'BBAS3 - Banco do Brasil',
                'category' => 'Ações Dividendos',
                'institution' => 'B3 / Banco do Brasil',
                'yield' => 'DY ~9.8% a.a.',
                'risk' => 'Médio',
                'minInvestment' => 'R$ 27,00',
                'description' => 'Empresa sólida, lucro recorde e repasse frequente de dividendos robustos aos acionistas.',
                'icon' => 'heroicon-m-currency-dollar',
                'colorClass' => 'text-amber-500 bg-amber-500/10 border-amber-500/20',
            ],
        ];

        return [
            'myInvestments' => $myInvestments,
            'totalInvested' => $totalInvested,
            'totalBalance' => $totalBalance,
            'totalProfit' => $totalProfit,
            'profitPercentage' => $profitPercentage,
            'typeBreakdown' => $typeBreakdown,
            'recommendations' => $recommendations,
        ];
    }
}
