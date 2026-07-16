<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\ReturnInvoiceDetail;
use App\Events\InvoiceStored;
use App\Events\InvoiceUpdated;
use App\Events\InvoiceCancelled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class InvoiceService
{
    /**
     * Store a new invoice.
     *
     * @param array $data Main invoice fields
     * @param array $products Products array
     * @param array $returnProducts Return products array
     * @return Invoice
     */
    public function store(array $data, array $products, array $returnProducts = []): Invoice
    {
        return DB::transaction(function () use ($data, $products, $returnProducts) {
            // Calculate next invoice_id (mimics current controller logic)
            $data['invoice_id'] = Invoice::count() > 0 ? Invoice::latest('id')->first()->invoice_id + 1 : 3000;
            
            // Set initial FBR fields
            $data['fbr_usin'] = $data['sr_number'];
            $data['fbr_invoice_type'] = 2; // Sale
            $data['fbr_status'] = 'pending';

            $invoice = Invoice::create($data);

            // Create products details
            foreach ($products as $product) {
                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product['id'],
                    'qty' => $product['qty'],
                    'price' => $product['price'],
                    'sale_tax' => $product['sale_tax'],
                    'ext_tax' => $product['ext_tax'],
                    'date' => $data['date']
                ]);
            }

            // Create return products details
            if (!empty($returnProducts)) {
                foreach ($returnProducts as $product) {
                    ReturnInvoiceDetail::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product['id'],
                        'qty' => $product['qty'],
                        'price' => $product['price'],
                        'sale_tax' => $product['sale_tax'],
                        'ext_tax' => $product['ext_tax'],
                        'date' => $data['date']
                    ]);
                }
            }

            // Fire event for listeners (like FBR) to pick up
            event(new InvoiceStored($invoice));

            // Invalidate branch products availability cache and dashboard cache
            Cache::forget("branch.products.{$data['branch_id']}");
            Cache::forget("dashboard.stats.all");

            return $invoice;
        });
    }

    /**
     * Update an existing invoice.
     *
     * @param Invoice $invoice
     * @param array $data Main invoice fields
     * @param array $products Products array
     * @param array $returnProducts Return products array
     * @return Invoice
     */
    public function update(Invoice $invoice, array $data, array $products, array $returnProducts = []): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $products, $returnProducts) {
            // Cache FBR fields before updating
            $oldUsin = $invoice->fbr_usin;
            $oldStatus = $invoice->fbr_status;
            $wasSubmitted = ($oldStatus === 'submitted');

            // Update main invoice table
            $invoice->update([
                "date" => $data['date'],
                "store_id" => $data['store_id'],
                "branch_id" => $data['branch_id'],
                "sr_number" => $data['sr_number'],
                "remarks" => $data['remarks'] ?? null,
                "total_qty" => $data['total_qty'],
                "total_price" => $data['total_price'],
                "discount" => $data['discount'] ?? 0,
            ]);

            // Re-assign FBR fields appropriately
            if ($wasSubmitted) {
                // If it was already submitted to FBR, this update requires a new USIN to be generated.
                // We will use the new sr_number as the new USIN for FBR.
                $invoice->update([
                    'fbr_usin' => $data['sr_number'],
                    'fbr_status' => 'pending',
                    'fbr_invoice_type' => 2,
                ]);
            } else {
                // Otherwise, just sync the current sr_number
                $invoice->update([
                    'fbr_usin' => $data['sr_number'],
                ]);
            }

            // Re-create items details
            InvoiceDetail::where('invoice_id', $invoice->id)->delete();
            ReturnInvoiceDetail::where('invoice_id', $invoice->id)->delete();

            foreach ($products as $product) {
                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product['id'],
                    'qty' => $product['qty'],
                    'price' => $product['price'],
                    'sale_tax' => $product['sale_tax'],
                    'ext_tax' => $product['ext_tax'],
                    'date' => $data['date']
                ]);
            }

            if (!empty($returnProducts)) {
                foreach ($returnProducts as $product) {
                    ReturnInvoiceDetail::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product['id'],
                        'qty' => $product['qty'],
                        'price' => $product['price'],
                        'sale_tax' => $product['sale_tax'],
                        'ext_tax' => $product['ext_tax'],
                        'date' => $data['date']
                    ]);
                }
            }

            // Fire event for listeners
            event(new InvoiceUpdated($invoice, $oldUsin, $wasSubmitted));

            // Invalidate branch products availability cache and dashboard cache
            Cache::forget("branch.products.{$invoice->branch_id}");
            if ($invoice->branch_id != $data['branch_id']) {
                Cache::forget("branch.products.{$data['branch_id']}");
            }
            Cache::forget("dashboard.stats.all");

            return $invoice;
        });
    }

    /**
     * Cancel an invoice (soft-cancel, required by FBR).
     *
     * @param Invoice $invoice
     * @return Invoice
     */
    public function cancel(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->is_cancelled) {
                return $invoice;
            }

            $wasSubmitted = ($invoice->fbr_status === 'submitted');

            $invoice->update([
                'is_cancelled' => true
            ]);

            if ($wasSubmitted) {
                // The cancellation (Credit Note) is pending submission
                $invoice->update([
                    'fbr_status' => 'pending',
                    'fbr_invoice_type' => 4, // Credit Note
                ]);
            } else {
                $invoice->update([
                    'fbr_status' => 'not_applicable',
                ]);
            }

            event(new InvoiceCancelled($invoice, $wasSubmitted));

            // Invalidate branch products availability cache and dashboard cache
            Cache::forget("branch.products.{$invoice->branch_id}");
            Cache::forget("dashboard.stats.all");

            return $invoice;
        });
    }
}
