<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $modelLabel = 'Assinatura';
    protected static ?string $pluralModelLabel = 'Assinaturas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_account_id')
                    ->label('Conta Bancária')
                    ->relationship('bankAccount', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nome do Serviço')
                    ->placeholder('ex: Netflix, Spotify, ChatGPT')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label('Valor (R$)')
                    ->required()
                    ->numeric()
                    ->prefix('R$'),
                Forms\Components\Select::make('type')
                    ->label('Periodicidade')
                    ->options([
                        'monthly' => 'Mensal',
                        'annual'  => 'Anual',
                    ])
                    ->default('monthly')
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Data de Início'),
                Forms\Components\Select::make('category')
                    ->label('Categoria')
                    ->options([
                        'Assinatura'     => 'Assinatura',
                        'Entretenimento' => 'Entretenimento',
                        'Lazer'          => 'Lazer',
                        'Serviços'       => 'Serviços',
                    ])
                    ->default('Assinatura'),
                Forms\Components\Toggle::make('active')
                    ->label('Assinatura Ativa')
                    ->default(true),
                Forms\Components\Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Assinatura')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->label('Conta')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Ciclo')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'monthly' ? 'Mensal' : 'Anual'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Assinatura Ativa'),
            ])
            ->actions([
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit'   => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
