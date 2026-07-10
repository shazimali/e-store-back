<?php

namespace App\Listeners\Fbr;

use App\Events\InvoiceStored;
use App\Jobs\Fbr\SubmitSaleInvoiceToFbrJob;
use Illuminate\Support\Facades\Log;

class FbrInvoiceStoredListener
{
    /**
     * Handle the event.
     *
     * @param InvoiceStored $event
     * @return void
     */
    public function handle(InvoiceStored $event): void
    {
        $invoice = $event->invoice;

        if (config('services.fbr.enabled')) {
            Log::info("FBR Stored Listener: Dispatching sale submission for Invoice ID {$invoice->id}");
            SubmitSaleInvoiceToFbrJob::dispatch($invoice);
        } else {
            Log::info("FBR Stored Listener: FBR is disabled. Setting status as not applicable for Invoice ID {$invoice->id}");
            $invoice->update(['fbr_status' => 'not_applicable']);
        }
    }
}
