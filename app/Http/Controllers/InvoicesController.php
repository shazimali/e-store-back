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
use Illuminate\Http\Request;
use PHPUnit\Framework\MockObject\Stub\ReturnReference;

class InvoicesController extends Controller
{
    function index(Request $request) {
        $this->authorize('invoice_access');
        
        $search = $request->search;
        $store_id = $request->store_id;
        $branch_id = $request->branch_id;

        $query = Invoice::query();
        $query->when($search, function($q) use($search){
           return $q->orWhere('total_qty','LIKE',"%{$search}%")
                ->orWhere('total_price','LIKE',"%{$search}%")
                ->orWhere('date','LIKE',"%{$search}%");
        });

        $query->when($store_id, function($q) use($store_id){
            return  $q->where('store_id',$store_id);
        });

        $query->when($branch_id, function($q) use($branch_id){
            return  $q->where('branch_id',$branch_id);
        });

        $invoices = $query->orderBy('invoice_id','DESC')->paginate($request->item_per_page);

        // if($request->search){
        //     $invoices = Invoice::where('store_id','LIKE',"%{$request->search}%")
        //     ->orWhere('total_qty','LIKE',"%{$request->search}%")
        //     ->orWhere('total_price','LIKE',"%{$request->search}%")
        //     ->orWhere('date','LIKE',"%{$request->search}%")
        //     ->paginate($request->item_per_page);
        // }

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
        $productIds = [];
            $deliverables = Deliverable::with('details')->where('branch_id',$branch_id)
                            ->notReturn()->get();
            foreach ($deliverables as $key => $deliverable) {
                foreach ($deliverable->details as $key => $detail) {
                    array_push($productIds, $detail->product_id);
                }
            }

            if(count($productIds)){
                $products = Product::whereIn('id',$productIds)->get();
                $all_products = [];
                foreach ($products as $key => $product) {
                    $product_block = [
                        'id' => $product->id,
                        'sku' => $product->sku,           
                        'code' => $product->code, 
                        'store' => $product->store ? $product->store->name : [],           
                        'sale_price' => $product->sale_price,     
                        'available_qty'=> $this->availableQtyForInvoice( $product->id, $branch_id),       
                        'name' => $product->name,           
                        'status' => $product->status,
                        'ext_tax' => CompanyDetail::take(1)->first()->ext_tax,
                        'sale_tax' => CompanyDetail::take(1)->first()->sale_tax,
                        'created_at' => $product->created_at->toDateString()
                    ];
                    array_push($all_products,$product_block);
                }
                return response()->json($all_products,200);
            }
            return [];
    }
    function fetchBranchProductsEdit($branch_id,$invoice_id) {
        $this->authorize('invoice_edit');
        $productIds = [];
            $deliverables = Deliverable::with('details')->where('branch_id',$branch_id)
                            ->where('is_return',0)->get();
            foreach ($deliverables as $key => $deliverable) {
                foreach ($deliverable->details as $key => $detail) {
                    array_push($productIds, $detail->product_id);
                }
            }

            if(count($productIds)){
                $products = Product::whereIn('id',$productIds)->get();
                $all_products = [];
                foreach ($products as $key => $product) {
                    $product_block = [
                        'id' => $product->id,
                        'sku' => $product->sku,           
                        'code' => $product->code, 
                        'store' => $product->store ? $product->store->name : [],           
                        'sale_price' => $product->sale_price,     
                        'available_qty'=> $this->availableQtyForInvoiceEdit( $product->id, $branch_id, $invoice_id),       
                        'name' => $product->name,           
                        'status' => $product->status,
                        'ext_tax' => CompanyDetail::take(1)->first()->ext_tax,
                        'sale_tax' => CompanyDetail::take(1)->first()->sale_tax,
                        'created_at' => $product->created_at->toDateString()
                    ];
                    array_push($all_products,$product_block);
                }
                return response()->json($all_products,200);
            }
            return [];
    }

    function availableQtyForInvoice($product_id,$branch_id) {
        $this->authorize('invoice_create');
        //delivered
        $delivered = Deliverable::with('details')
        ->where('is_return',0)->where('branch_id',$branch_id)->get();
        $delivered_qty = 0;
        foreach ($delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $delivered_qty +=  $detail->qty;
                }
            }
        }
        //return delivered
        $return_delivered = Deliverable::with('details')
        ->where('is_return',1)->where('branch_id',$branch_id)->get();
        $return_delivered_qty = 0;
        foreach ($return_delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $return_delivered_qty +=  $detail->qty;
                }
            }
        }
        //Invoiced
        $invoiced = Invoice::with('details','return_details')->where('branch_id',$branch_id)->get();
        $invoiced_qty = 0;
        foreach ($invoiced as $key => $del) {
            //sum invoice qty
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $invoiced_qty +=  $detail->qty;
                }
            }
            //subtract re-invoice qty
            foreach ($del->return_details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $invoiced_qty -=  $detail->qty;
                }
            }
        }
        return ($delivered_qty - $return_delivered_qty) - $invoiced_qty;
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
        return new InvoiceShowResource(Invoice::where('id',$id)->first());
    }

    function edit($id) {
        $this->authorize('invoice_edit');
            $invoice = Invoice::where('id',$id)->with('details','return_details')->first();
            $all_products = [];
            $all_return_products = [];
            foreach ($invoice->details as $key => $detail) {
                $product_block = [
                    'id' => $detail->product->id,
                    'sku' => $detail->product->sku,           
                    'code' => $detail->product->code, 
                    'price' => $detail->price, 
                    'qty' => $detail->qty, 
                    'store' => $detail->product->store ? $detail->product->store->name : [],           
                    'sale_price' => $detail->product->sale_price,     
                    'available_qty'=> $this->availableQtyForInvoiceEdit($detail->product->id, $invoice->branch_id, $invoice->id),       
                    'name' => $detail->product->name,           
                    'status' => $detail->product->status,
                    'ext_tax' => CompanyDetail::take(1)->first()->ext_tax,
                    'sale_tax' => CompanyDetail::take(1)->first()->sale_tax,
                    'created_at' => $detail->product->created_at->toDateString()
                ];
                array_push($all_products,$product_block);
            }
            if(count($invoice->return_details) > 0){
                foreach ($invoice->return_details as $key => $return_detail) {
                    $return_product_block = [
                        'id' => $return_detail->product->id,
                        'sku' => $return_detail->product->sku,           
                        'code' => $return_detail->product->code, 
                        'price' => $return_detail->price, 
                        'qty' => $return_detail->qty, 
                        'store' => $return_detail->product->store ? $return_detail->product->store->name : [],           
                        'sale_price' => $return_detail->product->sale_price,     
                        'available_qty'=> $this->availableQtyForInvoiceEdit($detail->product->id, $invoice->branch_id, $invoice->id),       
                        'name' => $return_detail->product->name,           
                        'status' => $return_detail->product->status,
                        'ext_tax' => CompanyDetail::take(1)->first()->ext_tax,
                        'sale_tax' => CompanyDetail::take(1)->first()->sale_tax,
                        'created_at' => $return_detail->product->created_at->toDateString()
                    ];
                    array_push($all_return_products,$return_product_block);
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

    function availableQtyForInvoiceEdit($product_id,$branch_id,$invoice_id) {
        $this->authorize('invoice_edit');
        //delivered
        $delivered = Deliverable::with('details')
        ->where('is_return',0)->where('branch_id',$branch_id)->get();
        $delivered_qty = 0;
        foreach ($delivered as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $delivered_qty =+  $detail->qty;
                }
            }
        }
        //Invoiced
        $invoiced = Invoice::with('details','return_details')
                    ->where('id','!=',$invoice_id)
                    ->where('branch_id',$branch_id)
                    ->get();
        $invoiced_qty = 0;
        //sum invoice qty
        foreach ($invoiced as $key => $del) {
            foreach ($del->details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $invoiced_qty =+  $detail->qty;
                }
            }
             //subtract re-invoice qty
             foreach ($del->return_details as $key => $detail) {
                if($detail->product_id == $product_id){
                    $invoiced_qty -=  $detail->qty;
                }
            }
        }
        return $delivered_qty - $invoiced_qty;
    }

    function update(UpdateInvoiceRequest $request, $id, InvoiceService $invoiceService) {
        $this->authorize('invoice_edit');
        $invoice = Invoice::where('id',$id)->first();
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
        $invoice = Invoice::where('id',$id)->first();
        $invoiceService->cancel($invoice);
        return response()->json(['message' => 'Invoice cancelled successfully']);
    }
}
