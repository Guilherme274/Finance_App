<?php

namespace App\Filament\Resources\BankAccountResource\Pages;

use App\Filament\Resources\BankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Illuminate\Database\Eloquent\Builder;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    public ?int $selectedAccountId = null;

    protected $listeners = [
        'accountFilterChanged' => 'setAccountFilter',
    ];

    public function setAccountFilter(?int $accountId = null): void
    {
        $this->selectedAccountId = $accountId;
        $this->resetPage();
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if ($this->selectedAccountId) {
            $query?->where('id', $this->selectedAccountId);
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
