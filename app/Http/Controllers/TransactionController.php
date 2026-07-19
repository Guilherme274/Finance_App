<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user       = User::firstOrCreate(
            ['email' => 'admin@financeapp.local'],
            ['name'  => 'Admin User', 'password' => bcrypt('password')]
        );
        $accountIds = BankAccount::where('user_id', $user->id)->pluck('id');

        $query = Transaction::whereIn('bank_account_id', $accountIds)
            ->orWhereNull('bank_account_id');

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('is_fixed')) {
            $query->where('is_fixed', (bool) $request->is_fixed);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $transactions = $query->orderBy('date', 'desc')->paginate(50)->withQueryString();

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description'    => 'nullable|string|max:255',
            'amount'         => 'required|numeric|min:0',
            'date'           => 'required|date',
            'type'           => 'required|in:CREDIT,DEBIT',
            'category'       => 'nullable|string|max:100',
            'is_fixed'       => 'nullable|boolean',
            'account_name'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
            'bank_account_id'=> 'nullable|exists:bank_accounts,id',
        ]);

        $transaction = Transaction::create($data);
        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        $data = $request->validate([
            'description'    => 'nullable|string|max:255',
            'amount'         => 'nullable|numeric|min:0',
            'date'           => 'nullable|date',
            'type'           => 'nullable|in:CREDIT,DEBIT',
            'category'       => 'nullable|string|max:100',
            'is_fixed'       => 'nullable|boolean',
            'account_name'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $transaction->update($data);
        return response()->json(['success' => true, 'transaction' => $transaction]);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:transactions,id',
        ]);

        Transaction::whereIn('id', $request->ids)->delete();
        
        return response()->json(['success' => true]);
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(['success' => true]);
    }

    public function syncMercadoPago()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('mercadopago:sync');
            $output = \Illuminate\Support\Facades\Artisan::output();
            return response()->json(['success' => true, 'message' => $output]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
