<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestmentResource\Pages;
use App\Models\Investment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestmentResource extends Resource
{
    protected static ?string $model = Investment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $modelLabel = 'Investimento';
    protected static ?string $pluralModelLabel = 'Investimentos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn() => auth()->id() ?? 1),
                Forms\Components\TextInput::make('name')
                    ->label('Nome do Investimento')
                    ->placeholder('ex: Tesouro Selic 2029, CDB 110% CDI')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('institution')
                    ->label('Corretora / Banco')
                    ->placeholder('ex: XP, NuInvest, BTG')
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount_invested')
                    ->label('Valor Aplicado (R$)')
                    ->required()
                    ->numeric()
                    ->prefix('R$')
                    ->default(0.00),
                Forms\Components\TextInput::make('balance')
                    ->label('Valor Saldo Atual (R$)')
                    ->required()
                    ->numeric()
                    ->prefix('R$')
                    ->default(0.00),
                Forms\Components\TextInput::make('rate')
                    ->label('Rentabilidade (%)')
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\Select::make('type')
                    ->label('Tipo de Ativo')
                    ->options([
                        'Renda Fixa' => 'Renda Fixa',
                        'Ações'      => 'Ações',
                        'FIIs'       => 'Fundos Imobiliários',
                        'Cripto'     => 'Criptomoedas',
                        'Outros'     => 'Outros',
                    ])
                    ->default('Renda Fixa'),
                Forms\Components\DatePicker::make('purchase_date')
                    ->label('Data de Aplicação'),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Data de Vencimento'),
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
                    ->label('Investimento')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('institution')
                    ->label('Corretora')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('amount_invested')
                    ->label('Aplicado')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo Atual')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Aplicação')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de Ativo')
                    ->options([
                        'Renda Fixa' => 'Renda Fixa',
                        'Ações'      => 'Ações',
                        'FIIs'       => 'Fundos Imobiliários',
                        'Cripto'     => 'Criptomoedas',
                        'Outros'     => 'Outros',
                    ]),
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
            'index'  => Pages\ListInvestments::route('/'),
            'create' => Pages\CreateInvestment::route('/create'),
            'edit'   => Pages\EditInvestment::route('/{record}/edit'),
        ];
    }
}
