<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MercadoPagoService
{
    protected string $baseUrl = 'https://api.mercadopago.com';

    /**
     * Fetch recent payments from Mercado Pago.
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPayments(int $limit = 50, int $offset = 0): array
    {
        $token = config('services.mercadopago.access_token') ?? env('MERCADOPAGO_ACCESS_TOKEN');

        if (!$token) {
            throw new \Exception('Mercado Pago Access Token is not configured.');
        }

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->get("{$this->baseUrl}/v1/payments/search", [
                'sort' => 'date_created',
                'criteria' => 'desc',
                'limit' => $limit,
                'offset' => $offset,
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch payments from Mercado Pago: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Fetch current account balance from Mercado Pago.
     * Returns null if unauthorized/forbidden or fails.
     */
    public function getAccountBalance(): ?float
    {
        $token = config('services.mercadopago.access_token') ?? env('MERCADOPAGO_ACCESS_TOKEN');

        if (!$token) {
            return null;
        }

        $tokenParts = explode('-', $token);
        $myUserId = end($tokenParts);

        try {
            $response = Http::withToken($token)
                ->withoutVerifying()
                ->get("{$this->baseUrl}/users/{$myUserId}/mercadopago_account/balance");

            if ($response->successful()) {
                $data = $response->json();
                return (float) ($data['total_amount'] ?? $data['available_balance'] ?? null);
            }
        } catch (\Throwable $e) {
            // Silence
        }

        return null;
    }

    /**
     * Sync Mercado Pago payments to a given BankAccount and update balance.
     */
    public function syncAccountTransactions(\App\Models\BankAccount $account, int $limit = 150): array
    {
        $syncedCount = 0;
        $offset = 0;
        $pageSize = 100; // Fetch in chunks of 100
        $maxLimit = 1000; // Safety guard limit

        do {
            $paymentsData = $this->getPayments($pageSize, $offset);
            $results = $paymentsData['results'] ?? [];
            if (empty($results)) {
                break;
            }

            foreach ($results as $item) {
                $extId = (string) ($item['id'] ?? '');
                if (!$extId) continue;

                $status = $item['status'] ?? 'approved';
                if ($status !== 'approved') continue;

                $rawAmount = (float) ($item['transaction_amount'] ?? 0);
                $rawDate   = substr($item['date_approved'] ?? $item['date_created'] ?? now()->toIso8601String(), 0, 10);
                $desc      = $item['description'] ?? $item['reason'] ?? 'Pagamento Mercado Pago';
                $cat       = $item['category_id'] ?? 'Outros';

                $token = config('services.mercadopago.access_token') ?? env('MERCADOPAGO_ACCESS_TOKEN');
                $tokenParts = explode('-', $token);
                $myUserId = (int) end($tokenParts);

                $collectorId = (int) ($item['collector']['id'] ?? ($item['collector_id'] ?? 0));
                $payerId = (int) ($item['payer']['id'] ?? ($item['payer_id'] ?? 0));

                // Determine if the transaction is an Inflow (CREDIT) or Outflow (DEBIT)
                $type = 'DEBIT';
                if ($myUserId > 0 && $collectorId === $myUserId) {
                    $type = 'CREDIT';
                } elseif ($myUserId > 0 && $payerId === $myUserId) {
                    $type = 'DEBIT';
                } else {
                    $netReceived = (float) ($item['transaction_details']['net_received_amount'] ?? 0);
                    if ($netReceived > 0 && $rawAmount > 0) {
                        $type = 'CREDIT';
                    }
                }

                // Force transfers/payments to Nubank/Fatura from Mercado Pago as DEBIT (outflow)
                $d = strtolower($desc);
                if (str_contains($d, 'nubank') || str_contains($d, 'fatura')) {
                    $type = 'DEBIT';
                }

                \App\Models\Transaction::updateOrCreate(
                    ['external_id' => 'mp_' . $extId],
                    [
                        'bank_account_id' => $account->id,
                        'description'     => $desc,
                        'amount'          => abs($rawAmount),
                        'date'            => $rawDate,
                        'type'            => $type,
                        'category'        => $cat,
                        'account_name'    => $account->name,
                    ]
                );
                $syncedCount++;
            }

            $offset += $pageSize;
        } while (count($results) === $pageSize && $offset < $maxLimit);

        // Try to update balance from API if available
        $apiBalance = $this->getAccountBalance();
        if ($apiBalance !== null) {
            $account->update(['balance' => $apiBalance]);
        }

        return [
            'synced'  => $syncedCount,
            'balance' => $account->fresh()->balance,
        ];
    }
}
