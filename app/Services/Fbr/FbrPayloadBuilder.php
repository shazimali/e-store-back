<?php

namespace App\Services\Fbr;

use App\Models\Invoice;
use App\Models\CompanyDetail;
use Carbon\Carbon;

class FbrPayloadBuilder
{
    protected string $defaultPctCode;

    public function __construct()
    {
        $this->defaultPctCode = config('services.fbr.default_pct_code', '99000000');
    }

    /**
     * Build the FBR payload for a Sale Invoice (InvoiceType = 2).
     *
     * @param Invoice $invoice
     * @return array
     */
    public function buildSale(Invoice $invoice): array
    {
        $branch = $invoice->branch;
        $company = CompanyDetail::first();
        $isExTax = (bool) $invoice->is_ex_tax;

        $items = [];
        $totalSaleValue = 0;
        $totalTaxCharged = 0;
        $totalQuantity = 0;
        $totalAmount = 0;

        // Process standard sales items
        if ($invoice->details) {
            foreach ($invoice->details as $detail) {
                $product = $detail->product;
                $pctCode = $this->defaultPctCode; // Standard default PCT Code
                
                $taxRate = (float) ($detail->sale_tax ?? 0);
                $extTaxRate = (float) ($detail->ext_tax ?? 0);
                $qty = (int) $detail->qty;
                $price = (float) $detail->price;

                if ($isExTax) {
                    $itemSaleValue = $price * $qty;
                    $itemSalesTax = $itemSaleValue * ($taxRate / 100);
                    $itemFurtherTax = $itemSaleValue * ($extTaxRate / 100);
                    $itemTotalAmount = $itemSaleValue + $itemSalesTax + $itemFurtherTax;
                } else {
                    $itemTotalAmount = $price * $qty;
                    $itemSaleValue = $itemTotalAmount / (1 + ($taxRate + $extTaxRate) / 100);
                    $itemSalesTax = $itemSaleValue * ($taxRate / 100);
                    $itemFurtherTax = $itemSaleValue * ($extTaxRate / 100);
                }

                $items[] = [
                    'ItemCode' => (string) ($product->code ?? $product->sku),
                    'ItemName' => (string) $product->name,
                    'PCTCode' => (string) $pctCode,
                    'Quantity' => $qty,
                    'TaxRate' => $taxRate,
                    'SaleValue' => round($itemSaleValue, 2),
                    'SalesTax' => round($itemSalesTax, 2),
                    'TotalAmount' => round($itemTotalAmount, 2),
                    'FurtherTax' => round($itemFurtherTax, 2),
                    'Discount' => 0.0,
                    'FederalExciseDuty' => 0.0,
                    'SalesTaxWithheld' => 0.0,
                ];

                $totalSaleValue += $itemSaleValue;
                $totalTaxCharged += $itemSalesTax;
                $totalQuantity += $qty;
                $totalAmount += $itemTotalAmount;
            }
        }

        // Process return items (if any, as negative in sale payload)
        if ($invoice->return_details) {
            foreach ($invoice->return_details as $detail) {
                $product = $detail->product;
                $pctCode = $this->defaultPctCode;

                $taxRate = (float) ($detail->sale_tax ?? 0);
                $extTaxRate = (float) ($detail->ext_tax ?? 0);
                $qty = (int) $detail->qty;
                $price = (float) $detail->price;

                if ($isExTax) {
                    $itemSaleValue = $price * $qty;
                    $itemSalesTax = $itemSaleValue * ($taxRate / 100);
                    $itemFurtherTax = $itemSaleValue * ($extTaxRate / 100);
                    $itemTotalAmount = $itemSaleValue + $itemSalesTax + $itemFurtherTax;
                } else {
                    $itemTotalAmount = $price * $qty;
                    $itemSaleValue = $itemTotalAmount / (1 + ($taxRate + $extTaxRate) / 100);
                    $itemSalesTax = $itemSaleValue * ($taxRate / 100);
                    $itemFurtherTax = $itemSaleValue * ($extTaxRate / 100);
                }

                $items[] = [
                    'ItemCode' => (string) ($product->code ?? $product->sku),
                    'ItemName' => (string) $product->name,
                    'PCTCode' => (string) $pctCode,
                    'Quantity' => -$qty,
                    'TaxRate' => $taxRate,
                    'SaleValue' => -round($itemSaleValue, 2),
                    'SalesTax' => -round($itemSalesTax, 2),
                    'TotalAmount' => -round($itemTotalAmount, 2),
                    'FurtherTax' => -round($itemFurtherTax, 2),
                    'Discount' => 0.0,
                    'FederalExciseDuty' => 0.0,
                    'SalesTaxWithheld' => 0.0,
                ];

                $totalSaleValue -= $itemSaleValue;
                $totalTaxCharged -= $itemSalesTax;
                $totalQuantity -= $qty;
                $totalAmount -= $itemTotalAmount;
            }
        }

        $posId = $branch && $branch->code && is_numeric($branch->code) ? (int) $branch->code : 0;

        return [
            'InvoiceNumber' => '',
            'POSID' => $posId,
            'USIN' => (string) ($invoice->sr_number ?? $invoice->invoice_id),
            'DateTime' => Carbon::parse($invoice->date)->format('Y-m-d H:i:s'),
            'BuyerNTN' => $branch ? $branch->ntn : null,
            'BuyerCNIC' => null,
            'BuyerName' => $branch ? $branch->name : null,
            'BuyerPhoneNumber' => $branch ? $branch->phone : null,
            'TotalSaleValue' => round($totalSaleValue, 2),
            'TotalTaxCharged' => round($totalTaxCharged, 2),
            'TotalAmount' => round($totalAmount, 2),
            'TotalQuantity' => $totalQuantity,
            'PaymentMode' => 1, // 1 = Cash
            'InvoiceType' => 2, // 2 = Sale
            'RefUSIN' => null,
            'Items' => $items,
        ];
    }

    /**
     * Build the FBR payload for a Credit Note (InvoiceType = 4).
     *
     * @param Invoice $invoice
     * @param string $refUsin
     * @return array
     */
    public function buildCreditNote(Invoice $invoice, string $refUsin): array
    {
        $payload = $this->buildSale($invoice);
        
        $payload['InvoiceType'] = 4; // 4 = Credit Note
        $payload['RefUSIN'] = $refUsin;

        // For a credit note, the amounts should negate the original transaction
        $payload['TotalSaleValue'] = -abs($payload['TotalSaleValue']);
        $payload['TotalTaxCharged'] = -abs($payload['TotalTaxCharged']);
        $payload['TotalAmount'] = -abs($payload['TotalAmount']);
        $payload['TotalQuantity'] = -abs($payload['TotalQuantity']);

        foreach ($payload['Items'] as &$item) {
            $item['Quantity'] = -abs($item['Quantity']);
            $item['SaleValue'] = -abs($item['SaleValue']);
            $item['SalesTax'] = -abs($item['SalesTax']);
            $item['TotalAmount'] = -abs($item['TotalAmount']);
            if (isset($item['FurtherTax'])) {
                $item['FurtherTax'] = -abs($item['FurtherTax']);
            }
        }

        return $payload;
    }
}
