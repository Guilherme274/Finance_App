<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request, MercadoPagoService $mpService)
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@financeapp.local'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );

        // --- Auto-seed Accounts if empty ---
        $allAccounts = BankAccount::where('user_id', $user->id)->get();
        if ($allAccounts->isEmpty()) {
            BankAccount::create([
                'user_id' => $user->id,
                'name' => 'Mercado Pago',
                'institution' => 'Mercado Pago',
                'balance' => 3987.00,
                'color' => '#009ee3',
                'type' => 'CHECKING',
            ]);
            BankAccount::create([
                'user_id' => $user->id,
                'name' => 'Nubank',
                'institution' => 'Nubank',
                'balance' => 0.00,
                'color' => '#8a05be',
                'type' => 'CHECKING',
            ]);
            $allAccounts = BankAccount::where('user_id', $user->id)->get();
        }

        // --- Fetch Mercado Pago Balance via API (with DB fallback) ---
        $mpAccount = $allAccounts->where('name', 'Mercado Pago')->first();
        if ($mpAccount) {
            $apiBalance = $mpService->getAccountBalance();
            if ($apiBalance !== null) {
                $mpAccount->update(['balance' => $apiBalance]);
                // Refresh the accounts list to get the updated balance
                $allAccounts = BankAccount::where('user_id', $user->id)->get();
            }
        }

        // --- Auto-map transactions with null bank_account_id to their respective bank accounts if the account name matches ---
        foreach ($allAccounts as $acc) {
            \App\Models\Transaction::whereNull('bank_account_id')
                ->where('account_name', $acc->name)
                ->update(['bank_account_id' => $acc->id]);
        }

        // --- Filters ---
        $selectedAccountId = $request->input('account_id');
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $currentMonth = $start->copy();

        $accounts = $allAccounts;
        if ($selectedAccountId) {
            $accounts = $allAccounts->where('id', $selectedAccountId);
        }

        $accountIds = $accounts->pluck('id')->toArray();
        $totalBalance = $accounts->sum('balance'); 

        // Helper function for transaction queries based on selected account
        $filterTransactions = function($query) use ($accountIds, $selectedAccountId) {
            if ($selectedAccountId) {
                return $query->whereIn('bank_account_id', $accountIds);
            }
            return $query->where(function($q) use ($accountIds) {
                $q->whereIn('bank_account_id', $accountIds)
                  ->orWhereNull('bank_account_id');
            });
        };

        // --- Transactions (recent) ---
        $transactions = Transaction::query();
        $transactions = $filterTransactions($transactions)
            ->orderBy('date', 'desc')
            ->limit(100)
            ->get();

        // --- Investments ---
        $investments = $user->investments()->orderBy('balance', 'desc')->get();
        $totalInvested      = $investments->sum('amount_invested');
        $totalInvestBalance = $investments->sum('balance');
        $investmentsGrouped = $investments->groupBy(fn($i) => $i->type ?: 'OUTROS');

        // --- KPIs (selected month) ---
        $monthlyTx = Transaction::query();
        $monthlyTx = $filterTransactions($monthlyTx)
            ->whereBetween('date', [$start, $end])
            ->get();

        $monthlyIncome   = $monthlyTx->where('type', 'CREDIT')->sum('amount');
        $monthlyExpenses = $monthlyTx->where('type', 'DEBIT')->sum('amount');
        $netBalance      = $monthlyIncome - $monthlyExpenses;

        // --- Fixed expenses ---
        $fixedExpensesQuery = Transaction::where('is_fixed', true);
        $fixedExpenses = $filterTransactions($fixedExpensesQuery)->get();
        $totalFixedMonthly = $fixedExpenses->sum('amount');

        // --- Expenses by category (selected month) ---
        $byCategoryQuery = Transaction::where('type', 'DEBIT')
            ->whereBetween('date', [$start, $end]);
        
        $byCategory = $filterTransactions($byCategoryQuery)
            ->get()
            ->groupBy(fn($t) => $t->category ?: 'Outros')
            ->map(fn($g) => round($g->sum('amount'), 2))
            ->sortDesc();

        // --- Calculate Daily Balance Evolution ---
        $tempBalance = $totalBalance;
        $monthStart = $start->copy();
        $monthEnd = $end->copy();

        // Roll balance back from today to the end of the selected month
        $today = Carbon::today();
        if ($monthEnd->gt($today)) {
            $monthEnd = $today->copy()->endOfDay();
        }

        $futureTxQuery = Transaction::where('date', '>', $monthEnd);
        $futureTx = $filterTransactions($futureTxQuery)->get();
        foreach ($futureTx as $tx) {
            if ($tx->type === 'CREDIT') {
                $tempBalance -= $tx->amount;
            } else {
                $tempBalance += $tx->amount;
            }
        }

        // Now $tempBalance is the balance at the end of the selected month (or today)
        $dailyBalances = [];
        $endDayStr = ($monthEnd->gt(Carbon::today())) ? Carbon::today()->toDateString() : $monthEnd->toDateString();

        $txInMonthQuery = Transaction::whereBetween('date', [$monthStart, $monthEnd])->orderBy('date', 'desc')->orderBy('id', 'desc');
        $txInMonth = $filterTransactions($txInMonthQuery)->get();

        $txGroupedByDay = $txInMonth->groupBy(fn($t) => Carbon::parse($t->date)->toDateString());

        // Fill balances day by day backwards
        $currDate = Carbon::parse($endDayStr);
        while($currDate->gte($monthStart)) {
            $dStr = $currDate->toDateString();
            $dailyBalances[$dStr] = round($tempBalance, 2);
            if (isset($txGroupedByDay[$dStr])) {
                foreach ($txGroupedByDay[$dStr] as $tx) {
                    if ($tx->type === 'CREDIT') {
                        $tempBalance -= $tx->amount;
                    } else {
                        $tempBalance += $tx->amount;
                    }
                }
            }
            $currDate->subDay();
        }
        ksort($dailyBalances);

        // Calculate min/max/average balance
        $minBalance = count($dailyBalances) > 0 ? min($dailyBalances) : 0;

        // --- Financial Insights & Decisions Calculations ---
        $savingsRate = ($monthlyIncome > 0) ? round(($netBalance / $monthlyIncome) * 100, 1) : 0;
        
        // Subscriptions calculation (reused from below)
        $subscriptionsQuery = \App\Models\Subscription::query();
        if ($selectedAccountId) {
            $subscriptionsQuery->whereIn('bank_account_id', $accountIds);
        } else {
            $subscriptionsQuery->where(function($q) use ($accountIds) {
                $q->whereIn('bank_account_id', $accountIds)->orWhereNull('bank_account_id');
            });
        }
        $subscriptions = $subscriptionsQuery->orderBy('created_at', 'desc')->get();
        $totalSubscriptionsMonthly = $subscriptions->filter(fn($s) => $s->active && $s->type === 'monthly')->sum('amount');
        $totalSubscriptionsMonthly += $subscriptions->filter(fn($s) => $s->active && $s->type === 'annual')->sum('amount') / 12;

        $fixedExpensesRatio = ($monthlyIncome > 0) ? round((($totalFixedMonthly + $totalSubscriptionsMonthly) / $monthlyIncome) * 100, 1) : 0;

        // Formulate recommendations
        $recommendations = [];
        $healthScore = 100;

        if ($monthlyIncome == 0) {
            $healthScore = 0;
            $recommendations[] = [
                'type' => 'warning',
                'title' => 'Sem receitas registradas',
                'desc' => 'Adicione ou sincronize suas receitas para iniciar a análise de saúde financeira.'
            ];
        } else {
            // Savings Rate Insight
            if ($savingsRate >= 20) {
                $recommendations[] = [
                    'type' => 'success',
                    'title' => 'Taxa de Poupança Excelente',
                    'desc' => "Você guardou {$savingsRate}% da sua renda este mês. Recomendamos aplicar a sobra de R$ " . number_format($netBalance, 2, ',', '.') . " em investimentos."
                ];
            } elseif ($savingsRate > 0) {
                $healthScore -= 15;
                $recommendations[] = [
                    'type' => 'info',
                    'title' => 'Taxa de Poupança Baixa',
                    'desc' => "Você poupou apenas {$savingsRate}% da sua renda. A meta ideal é pelo menos 20%. Tente reduzir gastos variáveis."
                ];
            } else {
                $healthScore -= 40;
                $recommendations[] = [
                    'type' => 'danger',
                    'title' => 'Orçamento no Vermelho',
                    'desc' => "Suas saídas superaram suas entradas em R$ " . number_format(abs($netBalance), 2, ',', '.') . ". Identifique gastos supérfluos imediatamente!"
                ];
            }

            // Fixed Cost Ratio Insight (Rule of 50/30/20)
            if ($fixedExpensesRatio > 50) {
                $healthScore -= 15;
                $recommendations[] = [
                    'type' => 'warning',
                    'title' => 'Custos Fixos Elevados',
                    'desc' => "Seus custos fixos comprometem {$fixedExpensesRatio}% da sua renda (o ideal é até 50%). Revise assinaturas ativas na aba dedicada."
                ];
            } else {
                $recommendations[] = [
                    'type' => 'success',
                    'title' => 'Custos Fixos Controlados',
                    'desc' => "Parabéns! Seus custos fixos estão em {$fixedExpensesRatio}% da sua renda, deixando margem para estilo de vida e investimentos."
                ];
            }

            // Balance-based Insights
            if ($minBalance < 0) {
                $healthScore -= 25;
                $recommendations[] = [
                    'type' => 'danger',
                    'title' => 'Saldo Negativo Detectado',
                    'desc' => "Cuidado: seu saldo consolidado ficou negativo durante este mês, atingindo a mínima de R$ " . number_format($minBalance, 2, ',', '.') . ". Evite o uso de cheque especial."
                ];
            } elseif ($minBalance < 500 && $totalBalance < 500) {
                $healthScore -= 10;
                $recommendations[] = [
                    'type' => 'warning',
                    'title' => 'Reserva de Liquidez Baixa',
                    'desc' => "Seu saldo mínimo registrado foi de R$ " . number_format($minBalance, 2, ',', '.') . ". É recomendável manter pelo menos R$ 1.000,00 como reserva rápida."
                ];
            } else {
                $recommendations[] = [
                    'type' => 'success',
                    'title' => 'Margem de Saldo Saudável',
                    'desc' => "Seu saldo se manteve seguro durante todo o mês, nunca ficando abaixo de R$ " . number_format($minBalance, 2, ',', '.') . "."
                ];
            }

            // Top Category Insight
            if ($byCategory->count() > 0) {
                $topCat = $byCategory->keys()->first();
                $topCatVal = $byCategory->first();
                $topCatRatio = round(($topCatVal / $monthlyExpenses) * 100, 1);
                if ($topCatRatio > 30) {
                    $healthScore -= 10;
                    $recommendations[] = [
                        'type' => 'info',
                        'title' => "Concentração em {$topCat}",
                        'desc' => "A categoria '{$topCat}' representa {$topCatRatio}% de todos os seus gastos no mês. Avalie estabelecer um teto mensal para ela."
                    ];
                }
            }
        }

        $healthScore = max(0, $healthScore);

        // --- Monthly chart data (6 months ending on selected month) ---
        $chartMonths  = [];
        $chartIncome  = [];
        $chartExpense = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $currentMonth->copy()->subMonths($i);
            $label = ucfirst($m->translatedFormat('M/y'));

            $mTxQuery = Transaction::whereYear('date', $m->year)->whereMonth('date', $m->month);
            $mTx = $filterTransactions($mTxQuery)->get();

            $chartMonths[]  = $label;
            $chartIncome[]  = round($mTx->where('type', 'CREDIT')->sum('amount'), 2);
            $chartExpense[] = round($mTx->where('type', 'DEBIT')->sum('amount'), 2);
        }

        return view('dashboard', compact(
            'user', 'accounts', 'allAccounts', 'selectedAccountId', 'startDate', 'endDate', 
            'currentMonth', 'transactions', 'totalBalance', 'netBalance',
            'investments', 'totalInvested', 'totalInvestBalance', 'investmentsGrouped',
            'monthlyIncome', 'monthlyExpenses',
            'fixedExpenses', 'totalFixedMonthly',
            'byCategory',
            'chartMonths', 'chartIncome', 'chartExpense',
            'subscriptions', 'totalSubscriptionsMonthly',
            'savingsRate', 'fixedExpensesRatio', 'healthScore', 'recommendations',
            'dailyBalances', 'minBalance'
        ));
    }

    public function updateBalance(Request $request, BankAccount $account)
    {
        $request->validate([
            'balance' => 'required|numeric'
        ]);

        $account->update([
            'balance' => $request->input('balance')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Saldo atualizado com sucesso!',
            'balance' => $account->balance
        ]);
    }

    public function chartData(Request $request)
    {
        $user    = User::firstOrCreate(
            ['email' => 'admin@financeapp.local'],
            ['name'  => 'Admin User', 'password' => bcrypt('password')]
        );
        $allAccounts = BankAccount::where('user_id', $user->id)->get();
        
        $selectedAccountId = $request->input('account_id');
        $selectedMonth = $request->input('month', Carbon::now()->month);
        $selectedYear = $request->input('year', Carbon::now()->year);
        $currentMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1);

        $accounts = $allAccounts;
        if ($selectedAccountId) {
            $accounts = $allAccounts->where('id', $selectedAccountId);
        }
        $accountIds = $accounts->pluck('id')->toArray();

        $filterTransactions = function($query) use ($accountIds, $selectedAccountId) {
            if ($selectedAccountId) {
                return $query->whereIn('bank_account_id', $accountIds);
            }
            return $query->where(function($q) use ($accountIds) {
                $q->whereIn('bank_account_id', $accountIds)
                  ->orWhereNull('bank_account_id');
            });
        };

        $months       = [];
        $incomeData   = [];
        $expenseData  = [];

        for ($i = 11; $i >= 0; $i--) {
            $m    = $currentMonth->copy()->subMonths($i);
            $mTxQuery = Transaction::whereYear('date', $m->year)->whereMonth('date', $m->month);
            $mTx = $filterTransactions($mTxQuery)->get();

            $months[]      = $m->translatedFormat('M/y');
            $incomeData[]  = round($mTx->where('type', 'CREDIT')->sum('amount'), 2);
            $expenseData[] = round($mTx->where('type', 'DEBIT')->sum('amount'), 2);
        }

        return response()->json([
            'months'   => $months,
            'income'   => $incomeData,
            'expenses' => $expenseData,
        ]);
    }
}
