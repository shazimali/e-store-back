<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Invoice $invoice;
    public bool $wasSubmitted;

    /**
     * Create a new event instance.
     *
     * @param Invoice $invoice
     * @param bool $wasSubmitted
     */
    public function __construct(Invoice $invoice, bool $wasSubmitted = false)
    {
        $this->invoice = $invoice;
        $this->wasSubmitted = $wasSubmitted;
    }
}
