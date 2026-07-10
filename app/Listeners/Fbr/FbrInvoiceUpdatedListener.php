<?php

namespace App\Listeners\Fbr;

use App\Events\InvoiceUpdated;
use App\Jobs\Fbr\SubmitSaleInvoiceToFbrJob;
use App\Jobs\Fbr\SubmitCreditNoteToFbrJob;
use Illuminate\Support\Facades\Log;

class FbrInvoiceUpdatedListener
{
    /**
     * Handle the event.
     *
     * @param InvoiceUpdated $event
     * @return void
     */
    public function handle(InvoiceUpdated $event): void
    {
        $invoice = $event->invoice;

        if (!config('services.fbr.enabled')) {
            Log::info("FBR Updated Listener: FBR is disabled. Setting status as not applicable for Invoice ID {$invoice->id}");
            $invoice->update(['fbr_status' => 'not_applicable']);
            return;
        }

        // If the invoice was previously successfully submitted, reverse it first, then submit new.
        if ($event->wasSubmitted && !empty($event->oldUsin)) {
            Log::info("FBR Updated Listener: Invoice {$invoice->id} was previously submitted. Dispatching Credit Note first.");
            // Submit credit note referencing the old USIN, and set resubmitNew=true to chain the new sale submission
            SubmitCreditNoteToFbrJob::dispatch($invoice, $event->oldUsin, true);
        } else {
            Log::info("FBR Updated Listener: Invoice {$invoice->id} was not submitted previously. Dispatching standard sale submission.");
            // Standard sale submission (either new or retrying failed)
            $invoice->update([
                'fbr_status' => 'pending',
                'fbr_invoice_type' => 2,
            ]);
            SubmitSaleInvoiceToFbrJob::dispatch($invoice);
        }
    }
}
