<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Invoice $invoice;
    public ?string $oldUsin;
    public bool $wasSubmitted;

    /**
     * Create a new event instance.
     *
     * @param Invoice $invoice
     * @param string|null $oldUsin
     * @param bool $wasSubmitted
     */
    public function __construct(Invoice $invoice, ?string $oldUsin = null, bool $wasSubmitted = false)
    {
        $this->invoice = $invoice;
        $this->oldUsin = $oldUsin;
        $this->wasSubmitted = $wasSubmitted;
    }
}
