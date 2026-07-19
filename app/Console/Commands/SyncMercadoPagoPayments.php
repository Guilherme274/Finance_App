<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MercadoPagoService;
use App\Models\Transaction;
use App\Models\BankAccount;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SyncMercadoPagoPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mercadopago:sync {--limit=50 : The number of payments to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync recent payments from Mercado Pago to the transactions table';

    /**
     * Execute the console command.
     */
    public function handle(MercadoPagoService $mercadoPagoService)
    {
        $this->info('Starting Mercado Pago sync...');

        $limit = (int) $this->option('limit');
        
        try {
            $data = $mercadoPagoService->getPayments($limit);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        $results = $data['results'] ?? [];
        
        if (empty($results)) {
            $this->info('No payments found.');
            return Command::SUCCESS;
        }

        $token = config('services.mercadopago.access_token') ?? env('MERCADOPAGO_ACCESS_TOKEN');
        $tokenParts = explode('-', $token);
        $myUserId = end($tokenParts);

        $mpAccount = BankAccount::where('name', 'Mercado Pago')->first();

        $imported = 0;

        foreach ($results as $payment) {
            // Only import approved payments
            if (($payment['status'] ?? '') !== 'approved') {
                continue;
            }

            $externalId = (string) $payment['id'];

            // Check if it already exists
            if (Transaction::where('external_id', $externalId)->exists()) {
                continue;
            }

            $amount = $payment['transaction_amount'];
            
            $collectorId = (string) ($payment['collector_id'] ?? '');
            $type = ($collectorId === $myUserId) ? 'CREDIT' : 'DEBIT';

            $description = $payment['description'] ?? '';
            
            // Try to extract payer or payee info
            $bankInfo = $payment['point_of_interaction']['transaction_data']['bank_info'] ?? null;
            
            if ($type === 'CREDIT') {
                $payerName = trim(($payment['payer']['first_name'] ?? '') . ' ' . ($payment['payer']['last_name'] ?? ''));
                if (!$payerName && isset($bankInfo['payer']['long_name'])) {
                    $payerName = $bankInfo['payer']['long_name'];
                }
                
                if ($payerName) {
                    $description = "Recebido de: $payerName" . ($description ? " ($description)" : '');
                } elseif (!$description) {
                    $description = "Recebimento Mercado Pago";
                }
            } else {
                $collectorName = $bankInfo['collector']['long_name'] ?? '';
                if ($collectorName) {
                    $description = "Pago para: $collectorName" . ($description ? " ($description)" : '');
                } elseif (!$description) {
                    $description = "Pagamento Mercado Pago";
                }
            }
            
            $paymentMethod = strtoupper($payment['payment_method_id'] ?? 'N/A');
            $paymentType = $payment['payment_type_id'] ?? '';
            $notes = "Forma de pagamento: $paymentMethod";
            if ($paymentType) {
                $notes .= " ($paymentType)";
            }

            $date = isset($payment['date_approved']) ? Carbon::parse($payment['date_approved']) : Carbon::now();

            Transaction::create([
                'bank_account_id' => $mpAccount ? $mpAccount->id : null,
                'external_id'  => $externalId,
                'description'  => Str::limit($description, 250),
                'amount'       => $amount,
                'date'         => $date,
                'type'         => $type,
                'category'     => 'Mercado Pago',
                'account_name' => 'Mercado Pago',
                'is_fixed'     => false,
                'notes'        => $notes,
            ]);

            $imported++;
        }

        $this->info("Sync completed! Imported {$imported} new transactions.");

        return Command::SUCCESS;
    }
}
