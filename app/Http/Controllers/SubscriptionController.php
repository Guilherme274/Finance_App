<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@financeapp.local'],
            ['name'  => 'Admin User', 'password' => bcrypt('password')]
        );
        $accountIds = BankAccount::where('user_id', $user->id)->pluck('id');

        $subscriptions = Subscription::whereIn('bank_account_id', $accountIds)
            ->orWhereNull('bank_account_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($subscriptions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255',
            'amount'          => 'required|numeric|min:0',
            'type'            => 'required|in:monthly,annual',
            'start_date'      => 'nullable|date',
            'category'        => 'nullable|string|max:100',
            'active'          => 'nullable|boolean',
            'notes'           => 'nullable|string',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);

        $subscription = Subscription::create($data);
        return response()->json(['success' => true, 'subscription' => $subscription]);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'name'            => 'nullable|string|max:255',
            'amount'          => 'nullable|numeric|min:0',
            'type'            => 'nullable|in:monthly,annual',
            'start_date'      => 'nullable|date',
            'category'        => 'nullable|string|max:100',
            'active'          => 'nullable|boolean',
            'notes'           => 'nullable|string',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
        ]);

        $subscription->update($data);
        return response()->json(['success' => true, 'subscription' => $subscription]);
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return response()->json(['success' => true]);
    }
}
