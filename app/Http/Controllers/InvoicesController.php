<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\InvoiceEditResource;
use App\Http\Resources\InvoiceListResoruce;
use App\Http\Resources\InvoiceShowResource;
use App\Http\Resources\ProductInvoiceShowResource;
use App\Http\Resources\Products\ProductsEditResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\CompanyDetail;
use App\Models\Deliverable;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Product;
use App\Models\ReturnInvoiceDetail;
use App\Models\Store;
use App\Services\InvoiceService;
use App\Repositories\InvoiceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InvoicesController extends Controller
{
    protected $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    function index(Request $request) {
        $this->authorize('invoice_access');
        $invoices = $this->invoiceRepository->paginatedList($request->all(), $request->item_per_page);
        return InvoiceListResoruce::collection($invoices);
    }

    function create() {
        $this->authorize('invoice_create');
        return response()->json([
            'stores' => StoreListResource::collection(Store::all()),
            'company' => new CompanyResource(CompanyDetail::take(1)->first()),
        ]);
    }

    function fetchStoreBranches($id) {
        $this->authorize('invoice_create');
        return response()->json(BranchListResource::collection(Branch::where('store_id',$id)->get()));
    }

    function fetchBranchProducts($branch_id) {
        $this->authorize('invoice_create');
        
        return Cache::remember("branch.products.{$branch_id}", 3600, function() use ($branch_id) {
            $productIds = [];
            $deliverables = Deliverable::with('details')->where('branch_id',$branch_id)
                            ->notReturn()->get();
            foreach ($deliverables as $deliverable) {
                foreach ($deliverable->details as $detail) {
                    $productIds[] = $detail->product_id;
                }
            }

            if(count($productIds)){
                $products = Product::with('store')->whereIn('id',$productIds)->get();
                $qtyMap = $this->invoiceRepository->getAvailableQtyMap($branch_id);
                $companyDetail = CompanyDetail::take(1)->first();
                $extTax = $companyDetail ? $companyDetail->ext_tax : 0;
                $saleTax = $companyDetail ? $companyDetail->sale_tax : 0;

                $all_products = [];
                foreach ($products as $product) {
                    $product_block = [
                        'id' => $product->id,
                        'sku' => $product->sku,           
                        'code' => $product->code, 
                        'store' => $product->store ? $product->store->name : [],           
                        'sale_price' => $product->sale_price,     
                        'available_qty'=> $qtyMap[$product->id] ?? 0,       
                        'name' => $product->name,           
                        'status' => $product->status,
                        'ext_tax' => $extTax,
                        'sale_tax' => $saleTax,
                        'created_at' => $product->created_at->toDateString()
                    ];
                    $all_products[] = $product_block;
                }
                return $all_products;
            }
            return [];
        });
    }

    function fetchBranchProductsEdit($branch_id,$invoice_id) {
        $this->authorize('invoice_edit');
        $productIds = [];
        $deliverables = Deliverable::with('details')->where('branch_id',$branch_id)
                        ->where('is_return',0)->get();
        foreach ($deliverables as $deliverable) {
            foreach ($deliverable->details as $detail) {
                $productIds[] = $detail->product_id;
            }
        }

        if(count($productIds)){
            $products = Product::with('store')->whereIn('id',$productIds)->get();
            $qtyMap = $this->invoiceRepository->getAvailableQtyMapForEdit($branch_id, $invoice_id);
            $companyDetail = CompanyDetail::take(1)->first();
            $extTax = $companyDetail ? $companyDetail->ext_tax : 0;
            $saleTax = $companyDetail ? $companyDetail->sale_tax : 0;

            $all_products = [];
            foreach ($products as $product) {
                $product_block = [
                    'id' => $product->id,
                    'sku' => $product->sku,           
                    'code' => $product->code, 
                    'store' => $product->store ? $product->store->name : [],           
                    'sale_price' => $product->sale_price,     
                    'available_qty'=> $qtyMap[$product->id] ?? 0,       
                    'name' => $product->name,           
                    'status' => $product->status,
                    'ext_tax' => $extTax,
                    'sale_tax' => $saleTax,
                    'created_at' => $product->created_at->toDateString()
                ];
                $all_products[] = $product_block;
            }
            return response()->json($all_products,200);
        }
        return [];
    }

    function store(StoreInvoiceRequest $request, InvoiceService $invoiceService) {
        $this->authorize('invoice_save');
        $invoice = $invoiceService->store(
            $request->all(),
            $request->products,
            $request->return_products ?? []
        );
        return response()->json($invoice->id);
    }

    function fetchInvoiceById($id) {
        $this->authorize('invoice_access');
        return new InvoiceShowResource($this->invoiceRepository->findById($id));
    }

    function edit($id) {
        $this->authorize('invoice_edit');
        $invoice = $this->invoiceRepository->findWithDetails($id);
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found.'], 404);
        }

        $qtyMap = $this->invoiceRepository->getAvailableQtyMapForEdit($invoice->branch_id, $invoice->id);
        $companyDetail = CompanyDetail::take(1)->first();
        $extTax = $companyDetail ? $companyDetail->ext_tax : 0;
        $saleTax = $companyDetail ? $companyDetail->sale_tax : 0;

        $all_products = [];
        $all_return_products = [];
        
        foreach ($invoice->details as $detail) {
            if (!$detail->product) continue;
            $product_block = [
                'id' => $detail->product->id,
                'sku' => $detail->product->sku,           
                'code' => $detail->product->code, 
                'price' => $detail->price, 
                'qty' => $detail->qty, 
                'store' => $detail->product->store ? $detail->product->store->name : [],           
                'sale_price' => $detail->product->sale_price,     
                'available_qty'=> $qtyMap[$detail->product->id] ?? 0,       
                'name' => $detail->product->name,           
                'status' => $detail->product->status,
                'ext_tax' => $extTax,
                'sale_tax' => $saleTax,
                'created_at' => $detail->product->created_at->toDateString()
            ];
            $all_products[] = $product_block;
        }

        if(count($invoice->return_details) > 0){
            foreach ($invoice->return_details as $return_detail) {
                if (!$return_detail->product) continue;
                $return_product_block = [
                    'id' => $return_detail->product->id,
                    'sku' => $return_detail->product->sku,           
                    'code' => $return_detail->product->code, 
                    'price' => $return_detail->price, 
                    'qty' => $return_detail->qty, 
                    'store' => $return_detail->product->store ? $return_detail->product->store->name : [],           
                    'sale_price' => $return_detail->product->sale_price,     
                    'available_qty'=> $qtyMap[$return_detail->product->id] ?? 0,       
                    'name' => $return_detail->product->name,           
                    'status' => $return_detail->product->status,
                    'ext_tax' => $extTax,
                    'sale_tax' => $saleTax,
                    'created_at' => $return_detail->product->created_at->toDateString()
                ];
                $all_return_products[] = $return_product_block;
            }
        }
        
        $data = [
            'invoice_id' => $invoice->invoice_id,
            'date' => $invoice->date,
            'is_return' => count($invoice->return_details) > 0 ?  true : false,
            'remarks' => $invoice->remarks,
            'branch_id' => $invoice->branch_id,
            'sr_number' => $invoice->sr_number,
            'store_id' => $invoice->store_id,
            'discount' => $invoice->discount,
            'products'=> $all_products,
            'return_products'=> $all_return_products,
            'total_qty' => $invoice->total_qty,
            'total_price' => $invoice->total_price,
            'fbr_status' => $invoice->fbr_status,
            'fbr_irn' => $invoice->fbr_irn,
            'fbr_usin' => $invoice->fbr_usin,
            'is_cancelled' => (bool) $invoice->is_cancelled,
        ];

        return response()->json($data,200);
    }

    function update(UpdateInvoiceRequest $request, $id, InvoiceService $invoiceService) {
        $this->authorize('invoice_edit');
        $invoice = $this->invoiceRepository->findById($id);
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found.'], 404);
        }
        $invoiceService->update(
            $invoice,
            $request->all(),
            $request->products,
            $request->return_products ?? []
        );
        return response()->json($invoice->id);
    }

    function destroy($id, InvoiceService $invoiceService) {
        $this->authorize('invoice_delete');
        $invoice = $this->invoiceRepository->findById($id);
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found.'], 404);
        }
        $invoiceService->cancel($invoice);
        return response()->json(['message' => 'Invoice cancelled successfully']);
    }
}
