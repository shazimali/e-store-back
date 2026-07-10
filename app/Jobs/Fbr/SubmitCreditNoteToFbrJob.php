<?php

namespace App\Jobs\Fbr;

use App\Models\Invoice;
use App\Services\Fbr\FbrService;
use App\Jobs\Fbr\SubmitSaleInvoiceToFbrJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitCreditNoteToFbrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Invoice $invoice;
    public string $refUsin;
    public bool $resubmitNew;
    public int $tries = 5;

    /**
     * Create a new job instance.
     *
     * @param Invoice $invoice
     * @param string $refUsin The original USIN being reversed
     * @param bool $resubmitNew Whether to submit the updated sale invoice after this succeeds
     */
    public function __construct(Invoice $invoice, string $refUsin, bool $resubmitNew = false)
    {
        $this->invoice = $invoice;
        $this->refUsin = $refUsin;
        $this->resubmitNew = $resubmitNew;
    }

    /**
     * Get the backoff times in seconds before retrying.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600];
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
        $result = $fbrService->submitCreditNote($this->invoice, $this->refUsin);

        if (!$result['success']) {
            $errorMessage = $result['error'] ?? 'Unknown FBR error';
            
            // Only retry if it is a connection/timeout issue
            $isConnectionError = empty($result['raw']) || str_contains($errorMessage, 'Connection exception') || str_contains($errorMessage, 'HTTP connection error');

            if ($isConnectionError) {
                Log::warning("FBR Credit Note transient failure for Invoice ID {$this->invoice->id}. Retrying. Error: {$errorMessage}");
                throw new \Exception("FBR Credit Note Connection Failure: {$errorMessage}");
            } else {
                Log::error("FBR Credit Note validation failure for Invoice ID {$this->invoice->id}. Will not retry. Error: {$errorMessage}");
            }
        } else {
            Log::info("FBR Credit Note submission successful for Invoice ID {$this->invoice->id}. Replaced USIN: {$this->refUsin}");

            // If we are editing, we now dispatch the new sale invoice submission
            if ($this->resubmitNew) {
                Log::info("Chaining Sale Invoice submission after successful FBR Credit Note for Invoice ID {$this->invoice->id}");
                $this->invoice->update([
                    'fbr_status' => 'pending',
                    'fbr_invoice_type' => 2,
                    'fbr_ref_usin' => $this->refUsin,
                ]);
                SubmitSaleInvoiceToFbrJob::dispatch($this->invoice);
            }
        }
    }
}
