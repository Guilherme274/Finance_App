<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SubscriptionController;

Route::redirect('/', '/admin');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/api/chart-data', [DashboardController::class, 'chartData'])->name('api.chart-data');

// Transactions (JSON API)
Route::post('/api/mercadopago/sync', [TransactionController::class, 'syncMercadoPago'])->name('mercadopago.sync');
Route::get('/api/transactions', [TransactionController::class, 'index'])->name('transactions.index');
Route::post('/api/transactions', [TransactionController::class, 'store'])->name('transactions.store');
Route::put('/api/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
Route::delete('/api/transactions/bulk', [TransactionController::class, 'bulkDestroy'])->name('transactions.bulkDestroy');
Route::delete('/api/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

// Subscriptions API
Route::get('/api/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
Route::post('/api/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
Route::put('/api/subscriptions/{subscription}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
Route::delete('/api/subscriptions/{subscription}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');

// Spreadsheet Import
Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
Route::post('/import/upload', [ImportController::class, 'upload'])->name('import.upload');

// Accounts balance update
Route::post('/api/accounts/{account}/balance', [DashboardController::class, 'updateBalance'])->name('accounts.update-balance');
