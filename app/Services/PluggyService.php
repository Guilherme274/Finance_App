<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PluggyService
{
    protected string $baseUrl = 'https://api.pluggy.ai';
    protected ?string $apiKey = null;

    public function __construct()
    {
        // API Key will be generated on demand
    }

    protected function ensureAuthenticated()
    {
        if (!$this->apiKey) {
            $this->authenticate();
        }
    }

    /**
     * Autenticar e obter API Key caso não tenha sido providenciada
     */
    public function authenticate()
    {
        $response = Http::withoutVerifying()->post("{$this->baseUrl}/auth", [
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
        $this->ensureAuthenticated();

        $payload = [];
        if ($itemId) {
            $payload['itemId'] = $itemId;
        }

        $response = Http::withoutVerifying()
            ->withHeader('X-API-KEY', $this->apiKey)
            ->post("{$this->baseUrl}/connect_token", $payload);

        if ($response->successful()) {
            return $response->json('accessToken');
        }

        throw new \Exception('Failed to create connect token: ' . $response->body());
    }

    public function getAccounts(string $itemId)
    {
        $this->ensureAuthenticated();

        $response = Http::withoutVerifying()
            ->withHeader('X-API-KEY', $this->apiKey)
            ->get("{$this->baseUrl}/accounts", [
                'itemId' => $itemId,
            ]);

        if ($response->successful()) {
            return $response->json('results') ?? [];
        }

        throw new \Exception('Error fetching accounts: ' . $response->body());
    }

    public function getTransactions(string $accountId)
    {
        $this->ensureAuthenticated();

        $response = Http::withoutVerifying()
            ->withHeader('X-API-KEY', $this->apiKey)
            ->get("{$this->baseUrl}/transactions", [
                'accountId' => $accountId,
            ]);

        if ($response->successful()) {
            return $response->json('results') ?? [];
        }

        throw new \Exception('Error fetching transactions: ' . $response->body());
    }

    /**
     * Buscar Investimentos atrelados a um Item
     */
    public function getInvestments(string $itemId)
    {
        $this->ensureAuthenticated();

        $response = Http::withoutVerifying()
            ->withHeader('X-API-KEY', $this->apiKey)
            ->get("{$this->baseUrl}/investments", [
                'itemId' => $itemId,
            ]);

        if ($response->successful()) {
            return $response->json('results') ?? [];
        }

        return []; // We return empty array silently for items that might not have investments supported
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
