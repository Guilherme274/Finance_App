<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BankAccount;
use App\Services\SpreadsheetParserService;

$parser = new SpreadsheetParserService();
$nubank = BankAccount::where('name', 'Nubank')->first();
$mp = BankAccount::where('name', 'Mercado Pago')->first();

$sampleRow = [
    'data' => '14/07/2026',
    'descricao' => 'Assinatura Netflix',
    'valor' => '-55,90'
];
$mapping = ['date' => 'data', 'description' => 'descricao', 'amount' => 'valor'];

echo "=== TEST NUBANK (MUST BE DEBIT) ===\n";
var_dump($parser->mapRow($sampleRow, $mapping, $nubank));

echo "\n=== TEST MERCADO PAGO (MUST BE DEBIT) ===\n";
var_dump($parser->mapRow($sampleRow, $mapping, $mp));

$positiveRow = [
    'data' => '14/07/2026',
    'descricao' => 'Pix Recebido',
    'valor' => '150,00'
];

echo "\n=== TEST POSITIVE AMOUNT (MUST BE CREDIT) ===\n";
var_dump($parser->mapRow($positiveRow, $mapping, $mp));
