<?php

namespace App\Services\Fbr;

use App\Models\Invoice;
use App\Services\Fbr\FbrPayloadBuilder;
use App\Services\Fbr\FbrClient;
use Illuminate\Support\Facades\Log;

class FbrService
{
    protected FbrPayloadBuilder $builder;
    protected FbrClient $client;
    protected bool $enabled;

    public function __construct(FbrPayloadBuilder $builder, FbrClient $client)
    {
        $this->builder = $builder;
        $this->client = $client;
        $this->enabled = (bool) config('services.fbr.enabled', false);
    }

    /**
     * Submit a Sale Invoice to FBR.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function submitSaleInvoice(Invoice $invoice): array
    {
        if (!$this->enabled) {
            Log::info("FBR integration is disabled. Skipping submission for Local Invoice ID: {$invoice->id}");
            return ['success' => true, 'status' => 'not_applicable', 'message' => 'FBR Integration Disabled'];
        }

        // Build Payload
        $payload = $this->builder->buildSale($invoice);

        // Send to FBR
        $result = $this->client->postInvoice($payload, $invoice->id);

        if ($result['success']) {
            $invoice->update([
                'fbr_irn' => $result['irn'],
                'fbr_status' => 'submitted',
                'fbr_response' => $result['raw'],
                'fbr_submitted_at' => now(),
            ]);
        } else {
            $invoice->update([
                'fbr_status' => 'failed',
                'fbr_response' => $result['raw'] ?? ['error' => $result['error']],
            ]);
        }

        return $result;
    }

    /**
     * Submit a Credit Note to FBR for reversing an invoice.
     *
     * @param Invoice $invoice
     * @param string $refUsin The original USIN/sr_number that is being reversed
     * @return array
     */
    public function submitCreditNote(Invoice $invoice, string $refUsin): array
    {
        if (!$this->enabled) {
            Log::info("FBR integration is disabled. Skipping Credit Note for Local Invoice ID: {$invoice->id}");
            return ['success' => true, 'status' => 'not_applicable', 'message' => 'FBR Integration Disabled'];
        }

        // Build Credit Note Payload
        $payload = $this->builder->buildCreditNote($invoice, $refUsin);

        // Send to FBR
        $result = $this->client->postInvoice($payload, $invoice->id);

        // Credit note is sent. Save this log to the invoice.
        // We will store the response in fbr_response or update its status.
        // Note: For full reversals (cancellations), the local invoice is flagged as cancelled.
        if ($result['success']) {
            Log::info("Credit Note successfully submitted to FBR for Local Invoice ID: {$invoice->id}");
            $invoice->update([
                'fbr_irn' => $result['irn'], // credit note IRN
                'fbr_status' => 'submitted',
                'fbr_response' => $result['raw'],
                'fbr_submitted_at' => now(),
            ]);
        } else {
            Log::error("Failed to submit Credit Note to FBR for Local Invoice ID: {$invoice->id}. Error: " . $result['error']);
            $invoice->update([
                'fbr_status' => 'failed',
                'fbr_response' => $result['raw'] ?? ['error' => $result['error']],
            ]);
        }

        return $result;
    }
}
