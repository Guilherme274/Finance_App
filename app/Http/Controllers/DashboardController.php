<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PluggyService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(PluggyService $pluggyService)
    {
        // For demonstration, auto-create a user if none exists
        $user = User::firstOrCreate(
            ['email' => 'admin@financeapp.local'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );

        $accounts = BankAccount::where('user_id', $user->id)->get();
        $transactions = Transaction::whereIn('bank_account_id', $accounts->pluck('id'))
                                   ->orderBy('date', 'desc')
                                   ->limit(50)
                                   ->get();

        $totalBalance = $accounts->sum('balance');

        // Generate pluggy connect token
        $connectToken = null;
        try {
            $connectToken = $pluggyService->createConnectToken();
        } catch (\Exception $e) {
            // Silently handle if keys are wrong for now, or display alert later
        }

        return view('dashboard', compact('user', 'accounts', 'transactions', 'totalBalance', 'connectToken'));
    }
}
