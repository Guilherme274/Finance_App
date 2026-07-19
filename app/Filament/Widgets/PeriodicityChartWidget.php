<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodicityChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Análise de Transações por Periodicidade';
    protected static ?int $sort = 2;

    public ?string $filter = 'monthly';

    protected function getFilters(): ?array
    {
        return [
            'daily'     => 'Diária (Últimos 14 Dias)',
            'weekly'    => 'Semanal (Últimas 8 Semanas)',
            'monthly'   => 'Mensal (Últimos 12 Meses)',
            'quarterly' => 'Trimestral (Últimos 6 Trimestres)',
            'yearly'    => 'Anual (Últimos 5 Anos)',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter ?? 'monthly';
        
        $userId = auth()->id();
        
        // Resolve account IDs for Nubank (Credit card) and Mercado Pago (Pix/Debit)
        $nubankAccountIds = \App\Models\BankAccount::where('user_id', $userId)
            ->where(function($q) {
                $q->where('name', 'like', '%nubank%')
                  ->orWhere('institution', 'like', '%nubank%')
                  ->orWhere('type', 'CREDIT');
            })
            ->pluck('id')
            ->toArray();

        $mpAccountIds = \App\Models\BankAccount::where('user_id', $userId)
            ->where(function($q) {
                $q->where('name', 'like', '%mercado%')
                  ->orWhere('name', 'like', '%pago%')
                  ->orWhere('institution', 'like', '%mercado%')
                  ->orWhere('institution', 'like', '%pago%')
                  ->orWhere('type', 'DEBIT')
                  ->orWhere('type', 'CHECKING');
            })
            ->whereNotIn('id', $nubankAccountIds)
            ->pluck('id')
            ->toArray();

        $nubankQuery = Transaction::whereIn('bank_account_id', $nubankAccountIds)->where('type', 'CREDIT');
        $mpQuery     = Transaction::whereIn('bank_account_id', $mpAccountIds)->where('type', 'DEBIT');

        $creditData = []; // Nubank (Crédito)
        $debitData  = []; // Mercado Pago (Pix / Débito)
        $labels     = [];

        switch ($activeFilter) {
            case 'daily':
                for ($i = 13; $i >= 0; $i--) {
                    $dt = now()->subDays($i)->format('Y-m-d');
                    $labels[] = now()->subDays($i)->format('d/m');
                    $creditData[] = (float) $nubankQuery->clone()->whereDate('date', $dt)->sum('amount');
                    $debitData[]  = (float) $mpQuery->clone()->whereDate('date', $dt)->sum('amount');
                }
                break;

            case 'weekly':
                for ($i = 7; $i >= 0; $i--) {
                    $start = now()->subWeeks($i)->startOfWeek()->format('Y-m-d');
                    $end   = now()->subWeeks($i)->endOfWeek()->format('Y-m-d');
                    $labels[] = 'Sem ' . now()->subWeeks($i)->weekOfYear . ' (' . now()->subWeeks($i)->startOfWeek()->format('d/m') . ')';
                    $creditData[] = (float) $nubankQuery->clone()->whereBetween('date', [$start, $end])->sum('amount');
                    $debitData[]  = (float) $mpQuery->clone()->whereBetween('date', [$start, $end])->sum('amount');
                }
                break;

            case 'monthly':
                for ($i = 11; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    $labels[] = $date->translatedFormat('M/Y');
                    $creditData[] = (float) $nubankQuery->clone()
                        ->whereYear('date', $date->year)
                        ->whereMonth('date', $date->month)
                        ->sum('amount');
                    $debitData[]  = (float) $mpQuery->clone()
                        ->whereYear('date', $date->year)
                        ->whereMonth('date', $date->month)
                        ->sum('amount');
                }
                break;

            case 'quarterly':
                for ($i = 5; $i >= 0; $i--) {
                    $date = now()->subQuarter($i * 3);
                    $q = ceil($date->month / 3);
                    $labels[] = "Q{$q} " . $date->year;
                    
                    $startMonth = ($q - 1) * 3 + 1;
                    $endMonth   = $q * 3;
                    
                    $creditData[] = (float) $nubankQuery->clone()
                        ->whereYear('date', $date->year)
                        ->whereBetween(DB::raw('MONTH(date)'), [$startMonth, $endMonth])
                        ->sum('amount');
                    $debitData[]  = (float) $mpQuery->clone()
                        ->whereYear('date', $date->year)
                        ->whereBetween(DB::raw('MONTH(date)'), [$startMonth, $endMonth])
                        ->sum('amount');
                }
                break;

            case 'yearly':
                for ($i = 4; $i >= 0; $i--) {
                    $year = now()->subYears($i)->year;
                    $labels[] = (string) $year;
                    $creditData[] = (float) $nubankQuery->clone()->whereYear('date', $year)->sum('amount');
                    $debitData[]  = (float) $mpQuery->clone()->whereYear('date', $year)->sum('amount');
                }
                break;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Nubank (Crédito)',
                    'data'  => $creditData,
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'Mercado Pago (Pix / Débito)',
                    'data'  => $debitData,
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
