<?php

namespace App\Jobs\Fbr;

use App\Models\Invoice;
use App\Services\Fbr\FbrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitSaleInvoiceToFbrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;
    public int $tries = 5;

    /**
     * Create a new job instance.
     *
     * @param Invoice $invoice
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the backoff times in seconds before retrying.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600]; // Exponential backoff
    }

    /**
     * Execute the job.
     *
     * @param FbrService $fbrService
     * @return void
     * @throws \Exception
     */
    public function handle(FbrService $fbrService): void
    {
        // Don't re-submit if already successfully submitted
        if ($this->invoice->fbr_status === 'submitted') {
            Log::info("Invoice ID {$this->invoice->id} is already submitted to FBR. Skipping job.");
            return;
        }

        $result = $fbrService->submitSaleInvoice($this->invoice);

        if (!$result['success']) {
            $errorMessage = $result['error'] ?? 'Unknown FBR error';
            
            // Only retry if it is a connection/timeout issue
            $isConnectionError = empty($result['raw']) || str_contains($errorMessage, 'Connection exception') || str_contains($errorMessage, 'HTTP connection error');

            if ($isConnectionError) {
                Log::warning("FBR Submission transient failure for Invoice ID {$this->invoice->id}. Retrying. Error: {$errorMessage}");
                throw new \Exception("FBR Connection Failure: {$errorMessage}");
            } else {
                Log::error("FBR Submission validation failure for Invoice ID {$this->invoice->id}. Will not retry. Error: {$errorMessage}");
            }
        } else {
            Log::info("FBR Submission successful for Invoice ID {$this->invoice->id}. FBR IRN: " . $result['irn']);
        }
    }
}
