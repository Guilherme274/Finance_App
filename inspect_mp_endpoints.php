<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = env('MERCADOPAGO_ACCESS_TOKEN');
$tokenParts = explode('-', $token);
$myUserId = end($tokenParts);

$endpoints = [
    'https://api.mercadopago.com/mercadopago_account/movements/search?limit=10',
    'https://api.mercadopago.com/v1/account/movements?limit=10',
    'https://api.mercadopago.com/v1/wallet/payments/search?limit=10',
    'https://api.mercadopago.com/v1/payments/search?limit=50&status=approved&operation_type=money_transfer',
    'https://api.mercadopago.com/v1/payments/search?limit=50&status=approved&payment_type_id=account_money',
    'https://api.mercadopago.com/v1/payments/search?limit=50&sort=date_created&criteria=desc',
];

foreach ($endpoints as $url) {
    echo "Testing URL: {$url}\n";
    try {
        $res = Illuminate\Support\Facades\Http::withToken($token)->withoutVerifying()->get($url);
        echo "Status: " . $res->status() . "\n";
        if ($res->successful()) {
            $data = $res->json();
            $results = $data['results'] ?? $data['data'] ?? [];
            echo "Results count: " . count($results) . "\n";
            if (!empty($results)) {
                $sample = array_slice($results, 0, 3);
                foreach ($sample as $s) {
                    echo "Sample item: " . json_encode($s) . "\n";
                }
            }
        } else {
            echo "Body: " . substr($res->body(), 0, 200) . "\n";
        }
    } catch (\Throwable $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "----------------------------------------\n";
}
