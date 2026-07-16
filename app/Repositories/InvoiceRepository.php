<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceRepository
{
    /**
     * Get paginated invoice list with eager loaded relations.
     *
     * @param array $filters
     * @param int|null $perPage
     * @return LengthAwarePaginator
     */
    public function paginatedList(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $storeId = $filters['store_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;

        $query = Invoice::query()->with(['store', 'branch']);

        $query->when($search, function ($q) use ($search) {
            return $q->where(function ($innerQuery) use ($search) {
                $innerQuery->orWhere('total_qty', 'LIKE', "%{$search}%")
                    ->orWhere('total_price', 'LIKE', "%{$search}%")
                    ->orWhere('date', 'LIKE', "%{$search}%");
            });
        });

        $query->when($storeId, function ($q) use ($storeId) {
            return $q->where('store_id', $storeId);
        });

        $query->when($branchId, function ($q) use ($branchId) {
            return $q->where('branch_id', $branchId);
        });

        return $query->orderBy('invoice_id', 'DESC')->paginate($perPage);
    }

    /**
     * Find a single invoice by ID.
     *
     * @param int $id
     * @return Invoice|null
     */
    public function findById(int $id): ?Invoice
    {
        return Invoice::where('id', $id)->first();
    }

    /**
     * Find an invoice by ID with its details and return details.
     *
     * @param int $id
     * @return Invoice|null
     */
    public function findWithDetails(int $id): ?Invoice
    {
        return Invoice::where('id', $id)->with(['details.product.store', 'return_details.product.store'])->first();
    }

    /**
     * Get a map of product_id => available_qty for a specific branch.
     *
     * @param int $branchId
     * @return array
     */
    public function getAvailableQtyMap(int $branchId): array
    {
        // 1. Delivered Qty (is_return = 0)
        $delivered = DB::table('deliverables')
            ->join('deliverable_details', 'deliverables.id', '=', 'deliverable_details.deliverable_id')
            ->where('deliverables.branch_id', $branchId)
            ->where('deliverables.is_return', 0)
            ->groupBy('deliverable_details.product_id')
            ->select('deliverable_details.product_id', DB::raw('SUM(deliverable_details.qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->all();

        // 2. Returned Deliverables (is_return = 1)
        $returnedDelivered = DB::table('deliverables')
            ->join('deliverable_details', 'deliverables.id', '=', 'deliverable_details.deliverable_id')
            ->where('deliverables.branch_id', $branchId)
            ->where('deliverables.is_return', 1)
            ->groupBy('deliverable_details.product_id')
            ->select('deliverable_details.product_id', DB::raw('SUM(deliverable_details.qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->all();

        // 3. Invoiced Qty
        $invoiced = DB::table('invoices')
            ->join('invoice_details', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->where('invoices.branch_id', $branchId)
            ->groupBy('invoice_details.product_id')
            ->select('invoice_details.product_id', DB::raw('SUM(invoice_details.qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->all();

        // 4. Returned Invoiced Qty
        $returnedInvoiced = DB::table('invoices')
            ->join('return_invoice_details', 'invoices.id', '=', 'return_invoice_details.invoice_id')
            ->where('invoices.branch_id', $branchId)
            ->groupBy('return_invoice_details.product_id')
            ->select('return_invoice_details.product_id', DB::raw('SUM(return_invoice_details.qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->all();

        // Get union of all product IDs
        $productIds = array_unique(array_merge(
            array_keys($delivered),
            array_keys($returnedDelivered),
            array_keys($invoiced),
            array_keys($returnedInvoiced)
        ));

        $qtyMap = [];
        foreach ($productIds as $productId) {
            $delQty = $delivered[$productId] ?? 0;
            $retDelQty = $returnedDelivered[$productId] ?? 0;
            $invQty = $invoiced[$productId] ?? 0;
            $retInvQty = $returnedInvoiced[$productId] ?? 0;

            $qtyMap[$productId] = ($delQty - $retDelQty) - ($invQty - $retInvQty);
        }

        return $qtyMap;
    }

    /**
     * Get a map of product_id => available_qty for editing a specific invoice.
     * Excludes the quantities of the invoice being edited from the invoiced total.
     *
     * @param int $branchId
     * @param int $invoiceId
     * @return array
     */
    public function getAvailableQtyMapForEdit(int $branchId, int $invoiceId): array
    {
        // 1. Delivered Qty (is_return = 0)
        $delivered = DB::table('deliverables')
            ->join('deliverable_details', 'deliverables.id', '=', 'deliverable_details.deliverable_id')
            ->where('deliverables.branch_id', $branchId)
            ->where('deliverables.is_return', 0)
            ->groupBy('deliverable_details.product_id')
            ->select('deliverable_details.product_id', DB::raw('SUM(deliverable_details.qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->all();

        // 2. Returned Deliverables (is_return = 1)
        $returnedDelivered = DB::table('deliverables')
            ->join('deliverable_details', 'deliverables.id', '=', 'deliverable_details.deliverable_id')
            ->where('deliverables.branch_id', $branchId)
            ->where('deliverables.is_return', 1)
            ->groupBy('deliverable_details.product_id')
            ->select('deliverable_details.product_id', DB::raw('SUM(deliverable_details.qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->all();

        // 3. Invoiced Qty (excluding current invoice)
        $invoiced = DB::table('invoices')
            ->join('invoice_details', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->where('invoices.branch_id', $branchId)
            ->where('invoices.id', '!=', $invoiceId)
            ->groupBy('invoice_details.product_id')
            ->select('invoice_details.product_id', DB::raw('SUM(invoice_details.qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->all();

        // 4. Returned Invoiced Qty (excluding current invoice)
        $returnedInvoiced = DB::table('invoices')
            ->join('return_invoice_details', 'invoices.id', '=', 'return_invoice_details.invoice_id')
            ->where('invoices.branch_id', $branchId)
            ->where('invoices.id', '!=', $invoiceId)
            ->groupBy('return_invoice_details.product_id')
            ->select('return_invoice_details.product_id', DB::raw('SUM(return_invoice_details.qty) as total_qty'))
            ->pluck('total_qty', 'product_id')
            ->all();

        // Get union of all product IDs
        $productIds = array_unique(array_merge(
            array_keys($delivered),
            array_keys($returnedDelivered),
            array_keys($invoiced),
            array_keys($returnedInvoiced)
        ));

        $qtyMap = [];
        foreach ($productIds as $productId) {
            $delQty = $delivered[$productId] ?? 0;
            $retDelQty = $returnedDelivered[$productId] ?? 0;
            $invQty = $invoiced[$productId] ?? 0;
            $retInvQty = $returnedInvoiced[$productId] ?? 0;

            $qtyMap[$productId] = ($delQty - $retDelQty) - ($invQty - $retInvQty);
        }

        return $qtyMap;
    }
}
