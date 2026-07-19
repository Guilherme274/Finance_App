<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategoryExpensesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Gastos por Categoria (Lazer, Entretenimento, Assinatura, Investimento...)';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $userId = auth()->id();
        $nubankAccountIds = \App\Models\BankAccount::where('user_id', $userId)
            ->where(function($q) {
                $q->where('name', 'like', '%nubank%')
                  ->orWhere('institution', 'like', '%nubank%')
                  ->orWhere('type', 'CREDIT');
            })
            ->pluck('id')
            ->toArray();

        $mpAccountIds = \App\Models\BankAccount::where('user_id', $userId)
            ->whereNotIn('id', $nubankAccountIds)
            ->pluck('id')
            ->toArray();

        $nubankCategories = Transaction::select('category', DB::raw('SUM(amount) as total'))
            ->whereIn('bank_account_id', $nubankAccountIds)
            ->where('type', 'CREDIT')
            ->groupBy('category')
            ->get();

        $mpCategories = Transaction::select('category', DB::raw('SUM(amount) as total'))
            ->whereIn('bank_account_id', $mpAccountIds)
            ->where('type', 'DEBIT')
            ->groupBy('category')
            ->get();

        $merged = [];
        foreach ([$nubankCategories, $mpCategories] as $cats) {
            foreach ($cats as $cat) {
                $catName = $cat->category ?: 'Outros';
                if (!isset($merged[$catName])) {
                    $merged[$catName] = 0;
                }
                $merged[$catName] += (float) $cat->total;
            }
        }
        arsort($merged);

        $labels = [];
        $data   = [];
        $colors = [];

        $colorMap = [
            'Lazer'                   => '#10b981', // green
            'Entretenimento'          => '#f59e0b', // amber
            'Assinatura'              => '#3b82f6', // blue
            'Investimento'            => '#8b5cf6', // purple
            'Alimentação / Essenciais'=> '#ef4444', // red
            'Transporte'              => '#ec4899', // pink
            'Moradia'                 => '#6366f1', // indigo
            'Outros'                  => '#9ca3af', // gray
        ];

        foreach ($merged as $catName => $total) {
            $labels[] = $catName;
            $data[]   = $total;
            $colors[] = $colorMap[$catName] ?? '#6b7280';
        }

        if (empty($labels)) {
            $labels = ['Nenhuma transação'];
            $data = [0];
            $colors = ['#e5e7eb'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total R$',
                    'data'  => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
