<?php

namespace App\Services\Subacquirers;

use App\Contracts\SubacquirerInterface;
use App\Models\Subacquirer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenericSubacquirer implements SubacquirerInterface
{
    protected Subacquirer $subacquirer;

    public function __construct(Subacquirer $subacquirer)
    {
        $this->subacquirer = $subacquirer;
    }

    public function getSubacquirer(): Subacquirer
    {
        return $this->subacquirer;
    }

    public function getBaseUrl(): string
    {
        return $this->subacquirer->base_url;
    }

    public function processPix(array $data): array
    {
        $url = rtrim($this->getBaseUrl(), '/') . '/pix';

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $data);

            $responseData = $response->json();

            Log::info("{$this->subacquirer->name} PIX Request", [
                'subacquirer' => $this->subacquirer->code,
                'url' => $url,
                'request' => $data,
                'response' => $responseData,
                'status' => $response->status(),
            ]);

            if (!$response->successful()) {
                throw new \Exception("{$this->subacquirer->name} API error: " . $response->body());
            }

            return [
                'success' => true,
                'data' => $responseData,
                'external_id' => $responseData['id'] ?? $responseData['transaction_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("{$this->subacquirer->name} PIX Error", [
                'subacquirer' => $this->subacquirer->code,
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function processWithdraw(array $data): array
    {
        $url = rtrim($this->getBaseUrl(), '/') . '/withdraw';

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $data);

            $responseData = $response->json();

            Log::info("{$this->subacquirer->name} Withdraw Request", [
                'subacquirer' => $this->subacquirer->code,
                'url' => $url,
                'request' => $data,
                'response' => $responseData,
                'status' => $response->status(),
            ]);

            if (!$response->successful()) {
                throw new \Exception("{$this->subacquirer->name} API error: " . $response->body());
            }

            return [
                'success' => true,
                'data' => $responseData,
                'external_id' => $responseData['id'] ?? $responseData['transaction_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("{$this->subacquirer->name} Withdraw Error", [
                'subacquirer' => $this->subacquirer->code,
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

