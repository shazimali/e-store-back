<?php

namespace App\Services\Fbr;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FbrClient
{
    protected ?string $token;
    protected string $postUrl;
    protected string $validateUrl;

    public function __construct()
    {
        $this->token = config('services.fbr.token');
        $this->postUrl = config('services.fbr.post_url');
        $this->validateUrl = config('services.fbr.validate_url');
    }

    /**
     * Send payload to FBR post invoice endpoint.
     *
     * @param array $payload
     * @param string|int|null $invoiceId Reference for logging
     * @return array
     */
    public function postInvoice(array $payload, $invoiceId = null): array
    {
        Log::info("FBR POST Request starting for Local Invoice ID: {$invoiceId}", [
            'url' => $this->postUrl,
            'usin' => $payload['USIN'] ?? null,
            'type' => $payload['InvoiceType'] ?? null,
        ]);

        try {
            $response = Http::withToken($this->token)
                ->timeout(15)
                ->post($this->postUrl, $payload);

            $body = $response->json();
            $statusCode = $response->status();

            Log::info("FBR POST Response for Local Invoice ID: {$invoiceId}", [
                'http_status' => $statusCode,
                'response_body' => $body
            ]);

            if ($response->successful()) {
                // If FBR returned status '00' or success, it means it's accepted
                // The IRN / FBR Invoice Number is typically in 'Result' or 'InvoiceNumber'
                $fbrStatusCode = $body['StatusCode'] ?? null;
                $fbrResult = $body['Result'] ?? null;
                $fbrError = $body['ErrorMessage'] ?? null;

                if ($fbrStatusCode === '00' || strtolower($fbrStatusCode) === 'success') {
                    return [
                        'success' => true,
                        'irn' => $fbrResult,
                        'error' => null,
                        'raw' => $body
                    ];
                }

                return [
                    'success' => false,
                    'irn' => null,
                    'error' => $fbrError ?? "FBR validation failed (Status: {$fbrStatusCode})",
                    'raw' => $body
                ];
            }

            return [
                'success' => false,
                'irn' => null,
                'error' => "HTTP connection error ({$statusCode})",
                'raw' => $body ?? []
            ];

        } catch (\Exception $e) {
            Log::error("FBR Connection Exception for Local Invoice ID: {$invoiceId}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'irn' => null,
                'error' => "Connection exception: " . $e->getMessage(),
                'raw' => []
            ];
        }
    }
}
