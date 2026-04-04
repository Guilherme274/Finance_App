<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PluggyService;
use Illuminate\Http\Request;

class PluggyController extends Controller
{
    public function syncItem(Request $request, PluggyService $pluggyService)
    {
        $itemId = $request->input('item_id');
        if (!$itemId) {
            return redirect()->back()->with('error', 'Nenhum Item ID recebido.');
        }

        // For demo, we attach to the first user
        $user = User::first();

        try {
            // Fetch Accounts
            $accounts = $pluggyService->getAccounts($itemId);
            
            foreach ($accounts as $acc) {
                // Save Account
                $bankAccount = BankAccount::updateOrCreate(
                    ['pluggy_account_id' => $acc['id']],
                    [
                        'user_id' => $user->id,
                        'pluggy_item_id' => $itemId,
                        'name' => $acc['name'],
                        'balance' => $acc['balance'],
                        'currency' => $acc['currencyCode'],
                        'type' => $acc['type'] ?? 'UNKNOWN',
                        'subtype' => $acc['subtype'] ?? 'UNKNOWN',
                    ]
                );

                // Fetch Transactions for this Account
                $transactions = $pluggyService->getTransactions($acc['id']);
                
                foreach ($transactions as $txn) {
                    Transaction::updateOrCreate(
                        ['pluggy_transaction_id' => $txn['id']],
                        [
                            'bank_account_id' => $bankAccount->id,
                            'description' => $txn['description'],
                            'amount' => $txn['amount'],
                            'date' => substr($txn['date'], 0, 10), // simplify datetime to date
                            'status' => $txn['status'],
                            'type' => $txn['amount'] < 0 ? 'DEBIT' : 'CREDIT',
                        ]
                    );
                }
            }

            return redirect()->back()->with('success', 'Contas e Transações sincronizadas com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao sincronizar Pluggy: ' . $e->getMessage());
        }
    }
}
