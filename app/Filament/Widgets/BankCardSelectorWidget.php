<?php

namespace App\Filament\Widgets;

use App\Models\BankAccount;
use Filament\Widgets\Widget;

class BankCardSelectorWidget extends Widget
{
    protected static string $view = 'filament.widgets.bank-card-selector';
    protected int | string | array $columnSpan = 'full';

    public ?int $selectedAccountId = null;

    public function mount(?int $selectedAccountId = null): void
    {
        $this->selectedAccountId = $selectedAccountId ?? request()->query('account');
    }

    public function selectAccount(?int $accountId): void
    {
        if ($this->selectedAccountId === $accountId) {
            $this->selectedAccountId = null;
        } else {
            $this->selectedAccountId = $accountId;
        }

        $this->dispatch('accountFilterChanged', accountId: $this->selectedAccountId);
    }

    protected function getViewData(): array
    {
        $userId = auth()->id();
        $accounts = BankAccount::where('user_id', $userId)->get();

        $cards = $accounts->map(function ($account) {
            $nameLower = strtolower($account->name . ' ' . ($account->institution ?? ''));
            $isNubank = str_contains($nameLower, 'nubank') || $account->type === 'CREDIT';
            $isMercadoPago = str_contains($nameLower, 'mercado') || str_contains($nameLower, 'pago');

            $typeLabel = match ($account->type) {
                'CREDIT' => 'Cartão de Crédito',
                'DEBIT' => 'Débito / Pix',
                'CHECKING' => 'Conta Corrente',
                'SAVINGS' => 'Poupança',
                default => 'Conta Bancária',
            };

            $bgStyle = match (true) {
                $isNubank => 'background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #8b5cf6 100%); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); shadow-purple-500/20;',
                $isMercadoPago => 'background: linear-gradient(135deg, #0369a1 0%, #0284c7 50%, #38bdf8 100%); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); shadow-sky-500/20;',
                default => 'background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); shadow-slate-500/20;',
            };

            // Card details
            $lastDigits = sprintf('%04d', ($account->id * 1337) % 10000);

            return [
                'id' => $account->id,
                'name' => $account->name,
                'institution' => $account->institution ?? 'Banco',
                'typeLabel' => $typeLabel,
                'balanceFormatted' => 'R$ ' . number_format($account->balance, 2, ',', '.'),
                'isNubank' => $isNubank,
                'isMercadoPago' => $isMercadoPago,
                'bgStyle' => $bgStyle,
                'cardNumber' => "•••• •••• •••• {$lastDigits}",
                'holderName' => strtoupper(auth()->user()?->name ?? 'MEMBRO FINANCE APP'),
                'expiry' => '12/28',
            ];
        });

        return [
            'cards' => $cards,
            'selectedAccountId' => $this->selectedAccountId,
        ];
    }
}
