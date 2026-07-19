<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Filament\Resources\BankAccountResource\RelationManagers;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $modelLabel = 'Conta Bancária';
    protected static ?string $pluralModelLabel = 'Contas Bancárias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn() => auth()->id()),
                Forms\Components\TextInput::make('name')
                    ->label('Nome da Conta')
                    ->required()
                    ->placeholder('ex: Nubank, Mercado Pago')
                    ->maxLength(255),
                Forms\Components\TextInput::make('institution')
                    ->label('Instituição')
                    ->placeholder('ex: Banco Nubank, Mercado Pago')
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Tipo de Conta')
                    ->options([
                        'CREDIT'   => 'Cartão de Crédito',
                        'DEBIT'    => 'Pix / Débito',
                        'CHECKING' => 'Conta Corrente',
                        'SAVINGS'  => 'Poupança',
                    ])
                    ->default('DEBIT')
                    ->required(),
                Forms\Components\TextInput::make('balance')
                    ->label('Saldo Atual (R$)')
                    ->required()
                    ->numeric()
                    ->prefix('R$')
                    ->default(0.00),
                Forms\Components\ColorPicker::make('color')
                    ->label('Cor de Identificação')
                    ->default('#8b5cf6'),
                Forms\Components\Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label('Cor'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Conta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('institution')
                    ->label('Instituição')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'CREDIT'   => 'warning',
                        'DEBIT'    => 'success',
                        'CHECKING' => 'info',
                        'SAVINGS'  => 'gray',
                        default    => 'primary',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'CREDIT'   => 'Crédito',
                        'DEBIT'    => 'Pix / Débito',
                        'CHECKING' => 'Conta Corrente',
                        'SAVINGS'  => 'Poupança',
                        default    => $state,
                    }),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo Atual')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Atualização')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('update_balance')
                    ->label('Atualizar Saldo')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('balance')
                            ->label('Novo Saldo Atual (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->default(fn(BankAccount $record) => $record->balance),
                    ])
                    ->action(function (BankAccount $record, array $data): void {
                        $record->update(['balance' => $data['balance']]);
                        \Filament\Notifications\Notification::make()
                            ->title('Saldo atualizado com sucesso!')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('sync_mercadopago')
                    ->label('Sincronizar MP')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn(BankAccount $record) => str_contains(strtolower($record->name . $record->institution), 'mercado'))
                    ->action(function (BankAccount $record, \App\Services\MercadoPagoService $service): void {
                        try {
                            $res = $service->syncAccountTransactions($record, 150);
                            \Filament\Notifications\Notification::make()
                                ->title("Sincronizado {$res['synced']} transações da API Mercado Pago!")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro ao sincronizar com Mercado Pago')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}
