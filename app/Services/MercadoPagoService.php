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
    public function getPayments(int $limit = 50, int $offset = 0, ?int $payerId = null): array
    {
        $token = config('services.mercadopago.access_token') ?? env('MERCADOPAGO_ACCESS_TOKEN');

        if (!$token) {
            throw new \Exception('Mercado Pago Access Token is not configured.');
        }

        $params = [
            'sort' => 'date_created',
            'criteria' => 'desc',
            'limit' => $limit,
            'offset' => $offset,
        ];

        if ($payerId) {
            $params['payer.id'] = $payerId;
        }

        $response = Http::withToken($token)
            ->withoutVerifying()
            ->get("{$this->baseUrl}/v1/payments/search", $params);

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

        $token = config('services.mercadopago.access_token') ?? env('MERCADOPAGO_ACCESS_TOKEN');
        $tokenParts = explode('-', $token ?? '');
        $myUserId = (int) end($tokenParts);

        // Fetch incoming (as collector) and outgoing (as payer) payments
        $incomingData = $this->getPayments($limit, 0);
        $outgoingData = $myUserId > 0 ? $this->getPayments($limit, 0, $myUserId) : ['results' => []];

        $combined = [];
        foreach (array_merge($incomingData['results'] ?? [], $outgoingData['results'] ?? []) as $item) {
            if (isset($item['id'])) {
                $combined[$item['id']] = $item;
            }
        }

        foreach ($combined as $item) {
            $extId = (string) ($item['id'] ?? '');
            if (!$extId) continue;

            $status = $item['status'] ?? 'approved';
            if ($status !== 'approved') continue;

            $rawAmount = (float) ($item['transaction_amount'] ?? 0);
            $rawDate   = substr($item['date_approved'] ?? $item['date_created'] ?? now()->toIso8601String(), 0, 10);
            
            $desc = $item['description'] 
                ?? $item['reason'] 
                ?? ($item['point_of_interaction']['transaction_data']['bank_info']['collector']['account_holder_name'] ?? null)
                ?? ($item['point_of_interaction']['transaction_data']['bank_info']['collector']['long_name'] ?? null)
                ?? ($item['point_of_interaction']['transaction_data']['bank_info']['collector']['account_alias'] ?? null)
                ?? ($item['point_of_interaction']['transaction_data']['bank_info']['payer']['account_holder_name'] ?? null)
                ?? 'Pagamento Mercado Pago';

            $cat = $item['category_id'] ?? 'Outros';

            $collectorId = (int) ($item['collector_id'] ?? ($item['collector']['id'] ?? 0));
            $payerId = (int) ($item['payer_id'] ?? ($item['payer']['id'] ?? 0));
            $subUnit = $item['point_of_interaction']['business_info']['sub_unit'] ?? null;

            // Determine if the transaction is an Inflow (CREDIT) or Outflow (DEBIT)
            if ($subUnit === 'money_outflows') {
                $type = 'DEBIT';
            } elseif ($subUnit === 'money_inflows') {
                $type = 'CREDIT';
            } elseif ($myUserId > 0 && $payerId === $myUserId && $collectorId !== $myUserId) {
                $type = 'DEBIT';
            } elseif ($myUserId > 0 && $collectorId === $myUserId && $payerId !== $myUserId) {
                $type = 'CREDIT';
            } else {
                $type = 'DEBIT';
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
