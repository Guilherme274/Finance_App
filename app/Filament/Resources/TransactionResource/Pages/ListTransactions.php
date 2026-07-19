<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    public ?int $selectedAccountId = null;

    protected $listeners = [
        'accountFilterChanged' => 'setAccountFilter',
    ];

    public function mount(): void
    {
        parent::mount();
        $accountParam = request()->query('account');
        if ($accountParam) {
            $this->selectedAccountId = (int) $accountParam;
        }
    }

    public function setAccountFilter(?int $accountId = null): void
    {
        $this->selectedAccountId = $accountId;
        $this->resetPage();
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if ($this->selectedAccountId) {
            $query?->where('bank_account_id', $this->selectedAccountId);
        }

        return $query;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\BankCardSelectorWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
