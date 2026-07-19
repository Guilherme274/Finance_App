<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\BankAccount;
use App\Models\SpreadsheetImport;
use App\Models\Transaction;
use App\Services\ExportService;
use App\Services\SpreadsheetParserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $modelLabel = 'Transação';
    protected static ?string $pluralModelLabel = 'Transações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_account_id')
                    ->label('Conta Bancária')
                    ->options(fn() => BankAccount::get()->pluck('display_name', 'id'))
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $acc = BankAccount::find($state);
                            if ($acc) {
                                $accName = strtolower($acc->name . ' ' . $acc->institution);
                                if (str_contains($accName, 'nubank') || $acc->type === 'CREDIT') {
                                    $set('type', 'CREDIT');
                                } elseif (str_contains($accName, 'mercado') || str_contains($accName, 'pago') || $acc->type === 'DEBIT') {
                                    $set('type', 'DEBIT');
                                }
                            }
                        }
                    }),
                Forms\Components\TextInput::make('description')
                    ->label('Descrição')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label('Valor (R$)')
                    ->required()
                    ->numeric()
                    ->prefix('R$'),
                Forms\Components\DatePicker::make('date')
                    ->label('Data')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Tipo de Transação')
                    ->options([
                        'CREDIT' => 'Crédito',
                        'DEBIT'  => 'Pix / Débito',
                    ])
                    ->required(),
                Forms\Components\Select::make('category')
                    ->label('Categoria')
                    ->options([
                        'Lazer'                   => 'Lazer',
                        'Entretenimento'          => 'Entretenimento',
                        'Assinatura'              => 'Assinatura',
                        'Investimento'            => 'Investimento',
                        'Alimentação / Essenciais'=> 'Alimentação / Essenciais',
                        'Transporte'              => 'Transporte',
                        'Moradia'                 => 'Moradia',
                        'Outros'                  => 'Outros',
                    ])
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('category_name')->label('Nova Categoria')->required()
                    ])
                    ->createOptionUsing(fn (array $data) => $data['category_name']),
                Forms\Components\Toggle::make('is_fixed')
                    ->label('Gasto Fixo Mensal')
                    ->default(false),
                Forms\Components\Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bankAccount.display_name')
                    ->label('Conta')
                    ->default(fn(Transaction $record) => $record->account_name ?? 'N/A')
                    ->badge()
                    ->color(fn(Transaction $record): string => match (strtolower($record->bankAccount?->name ?? $record->account_name ?? '')) {
                        'nubank'       => 'purple',
                        'mercado pago' => 'info',
                        default        => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Lazer'          => 'success',
                        'Entretenimento' => 'warning',
                        'Assinatura'     => 'info',
                        'Investimento'   => 'purple',
                        default          => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'CREDIT' ? 'warning' : 'danger')
                    ->formatStateUsing(fn(string $state): string => $state === 'CREDIT' ? 'Crédito' : 'Pix / Débito'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->color(fn(Transaction $record): string => $record->type === 'CREDIT' ? 'success' : 'danger')
                    ->weight('bold'),
                Tables\Columns\IconColumn::make('is_fixed')
                    ->label('Fixo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label('Conta Bancária')
                    ->options(fn() => BankAccount::get()->pluck('display_name', 'id')),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options([
                        'Lazer'                   => 'Lazer',
                        'Entretenimento'          => 'Entretenimento',
                        'Assinatura'              => 'Assinatura',
                        'Investimento'            => 'Investimento',
                        'Alimentação / Essenciais'=> 'Alimentação / Essenciais',
                        'Transporte'              => 'Transporte',
                        'Moradia'                 => 'Moradia',
                        'Outros'                  => 'Outros',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo de Transação')
                    ->options([
                        'CREDIT' => 'Crédito',
                        'DEBIT'  => 'Pix / Débito',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('De'),
                        Forms\Components\DatePicker::make('until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('import_spreadsheet')
                    ->label('Importar Planilha (CSV/XLSX)')
                    ->icon('heroicon-o-document-arrow-up')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('bank_account_id')
                            ->label('Conta de Origem dos Dados')
                            ->options(fn() => BankAccount::get()->pluck('display_name', 'id'))
                            ->required()
                            ->helperText('As transações serão vinculadas a esta conta. Nubank = Crédito | Mercado Pago = Pix/Débito.'),
                        Forms\Components\FileUpload::make('spreadsheet')
                            ->label('Arquivo da Planilha (.csv, .xlsx)')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'])
                            ->required()
                            ->disk('public')
                            ->storeFiles(true)
                            ->directory('imports'),
                    ])
                    ->action(function (array $data, SpreadsheetParserService $parser): void {
                        $bankAccount = BankAccount::find($data['bank_account_id']);
                        $filePath = Storage::disk('public')->path($data['spreadsheet']);
                        if (!file_exists($filePath)) {
                            $filePath = Storage::disk('local')->path($data['spreadsheet']);
                        }

                        try {
                            $uploadedFile = new UploadedFile($filePath, basename($filePath));
                            $rows = $parser->parse($uploadedFile);

                            if (empty($rows)) {
                                throw new \Exception('A planilha está vazia ou não possui cabeçalhos reconhecíveis.');
                            }

                            $headers = array_keys($rows[0]);
                            $mapping = $parser->detectColumnMapping($headers);
                            
                            $importedCount = 0;
                            foreach ($rows as $row) {
                                $mapped = $parser->mapRow($row, $mapping, $bankAccount);
                                if ($mapped) {
                                    Transaction::create($mapped);
                                    $importedCount++;
                                }
                            }

                            SpreadsheetImport::create([
                                'user_id'       => auth()->id() ?? 1,
                                'filename'      => basename($filePath),
                                'type'          => 'transactions',
                                'rows_imported' => $importedCount,
                                'status'        => 'success',
                                'column_mapping'=> $mapping,
                                'notes'         => "Importado para conta {$bankAccount?->name}",
                            ]);

                            Notification::make()
                                ->title("Importação concluída com sucesso!")
                                ->body("Foram importadas {$importedCount} transações para a conta {$bankAccount?->name}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title("Erro na importação")
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('export_xlsx')
                    ->label('Exportar XLSX')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function (Table $table, ExportService $exportService) {
                        $records = $table->getRecords();
                        return $exportService->exportXlsx($records, 'transacoes_' . date('Y-m-d') . '.csv');
                    }),
                Tables\Actions\Action::make('export_pdf')
                    ->label('Exportar PDF')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('warning')
                    ->action(function (Table $table, ExportService $exportService) {
                        $records = $table->getRecords();
                        return $exportService->exportPdf($records, 'Relatório Analítico de Transações');
                    }),
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
            'index'  => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit'   => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
