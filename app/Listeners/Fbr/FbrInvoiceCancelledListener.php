<?php

namespace App\Listeners\Fbr;

use App\Events\InvoiceCancelled;
use App\Jobs\Fbr\SubmitCreditNoteToFbrJob;
use Illuminate\Support\Facades\Log;

class FbrInvoiceCancelledListener
{
    /**
     * Handle the event.
     *
     * @param InvoiceCancelled $event
     * @return void
     */
    public function handle(InvoiceCancelled $event): void
    {
        $invoice = $event->invoice;

        if (!config('services.fbr.enabled')) {
            Log::info("FBR Cancelled Listener: FBR is disabled. Setting status as not applicable for Invoice ID {$invoice->id}");
            $invoice->update(['fbr_status' => 'not_applicable']);
            return;
        }

        // Only reverse if it was actually submitted to FBR
        if ($event->wasSubmitted && !empty($invoice->fbr_usin)) {
            Log::info("FBR Cancelled Listener: Invoice {$invoice->id} was previously submitted. Dispatching full Credit Note reversal.");
            // Submit credit note referencing its own USIN, resubmitNew=false since this is a cancellation
            SubmitCreditNoteToFbrJob::dispatch($invoice, $invoice->fbr_usin, false);
        } else {
            Log::info("FBR Cancelled Listener: Invoice {$invoice->id} was never submitted. FBR sync skipped.");
            $invoice->update(['fbr_status' => 'not_applicable']);
        }
    }
}
