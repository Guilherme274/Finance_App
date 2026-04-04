<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PluggyService
{
    protected string $baseUrl = 'https://api.pluggy.ai';
    protected ?string $apiKey = null;

    public function __construct()
    {
        // Try getting API Key directly from env first
        $this->apiKey = env('PLUGGY_API_KEY');
        
        if (!$this->apiKey) {
            $this->authenticate();
        }
    }

    /**
     * Autenticar e obter API Key caso não tenha sido providenciada
     */
    public function authenticate()
    {
        $response = Http::post("{$this->baseUrl}/auth", [
            'clientId' => env('PLUGGY_CLIENT_ID'),
            'clientSecret' => env('PLUGGY_CLIENT_SECRET'),
        ]);

        if ($response->successful()) {
            $this->apiKey = $response->json('apiKey');
        } else {
            throw new \Exception('Failed to authenticate with Pluggy: ' . $response->body());
        }
    }

    /**
     * Criar um Connect Token para o Widget do Frontend
     */
    public function createConnectToken(?string $itemId = null)
    {
        $payload = [];
        if ($itemId) {
            $payload['itemId'] = $itemId;
        }

        $response = Http::withHeader('X-API-KEY', $this->apiKey)
            ->post("{$this->baseUrl}/connect_tokens", $payload);

        if ($response->successful()) {
            return $response->json('accessToken');
        }

        throw new \Exception('Failed to create connect token: ' . $response->body());
    }

    /**
     * Buscar Contas atreladas a um Item (Conexão)
     */
    public function getAccounts(string $itemId)
    {
        $response = Http::withHeader('X-API-KEY', $this->apiKey)
            ->get("{$this->baseUrl}/accounts", [
                'itemId' => $itemId,
            ]);

        if ($response->successful()) {
            return $response->json('results');
        }

        return [];
    }

    /**
     * Buscar Transações atreladas a uma Conta
     */
    public function getTransactions(string $accountId)
    {
        $response = Http::withHeader('X-API-KEY', $this->apiKey)
            ->get("{$this->baseUrl}/transactions", [
                'accountId' => $accountId,
            ]);

        if ($response->successful()) {
            return $response->json('results');
        }

        return [];
    }
    
    /**
     * Buscar todos os Items do usuário na Pluggy
     */
    public function getItems()
    {
        $response = Http::withHeader('X-API-KEY', $this->apiKey)
            ->get("{$this->baseUrl}/items");

        if ($response->successful()) {
            return $response->json('results');
        }
        
        return [];
    }
}
