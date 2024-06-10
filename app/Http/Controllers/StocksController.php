<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchListResource;
use App\Http\Resources\StockListResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\Deliverable;
use App\Models\DeliverableDetail;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class StocksController extends Controller
{
    function index(Request $request) {
        $this->authorize('stock_access');
        return response()->json( StoreListResource::collection(Store::all())); 
    }

    function fetchBranchesByStoreId($id) {
        $this->authorize('stock_access');
       $branches =  Branch::where('store_id',$id)->get();
       $deliverableIDs = Deliverable::where('store_id',$id)->pluck('id');
       $invoiceIDs = Invoice::where('store_id',$id)->pluck('id');
       $products = Product::whereHas('deliverables',function($q) use($deliverableIDs){
            $q->whereIn('deliverable_id',$deliverableIDs);
        })
        ->with(['invoices' => function($q) use($invoiceIDs){
            $q->whereIn('invoice_id',$invoiceIDs);
            }
       ])->with(['returnInvoices' => function($q) use($invoiceIDs){
            $q->whereIn('invoice_id',$invoiceIDs);
            }
        ])->get();

       $all_products = [];
    foreach ($products as $product) {
            $delIds =  Deliverable::notReturn()->whereIn('id',$product->deliverables->pluck('deliverable_id'))->pluck('id');
            $reDelIds =  Deliverable::return()->whereIn('id',$product->deliverables->pluck('deliverable_id'))->pluck('id');
            $invoices_qty = $product->invoices->sum('qty');
            $return_invoices_qty = $product->returnInvoices->sum('qty');
            $product_data =  [
                'title' => $product->name,
                'in_stock' => $product->deliverables ? 
                    (DeliverableDetail::whereIn('deliverable_id',$delIds)->where('product_id',$product->id)->sum('qty') -  DeliverableDetail::whereIn('deliverable_id',$reDelIds)->where('product_id',$product->id)->sum('qty'))
                    : 0,
                'out_stock' => $invoices_qty - $return_invoices_qty < 0 ? -($invoices_qty - $return_invoices_qty) : $invoices_qty - $return_invoices_qty,
                'total' => ( DeliverableDetail::whereIn('deliverable_id',$delIds)->where('product_id',$product->id)->sum('qty') -  DeliverableDetail::whereIn('deliverable_id',$reDelIds)->where('product_id',$product->id)->sum('qty')) - ($invoices_qty - $return_invoices_qty < 0 ? -($invoices_qty - $return_invoices_qty) : $invoices_qty - $return_invoices_qty)
            ];  
            
            array_push($all_products,$product_data);
        }

       return response()->json([
        'branches' => BranchListResource::collection($branches),
        // 'stocks' => StockListResource::collection($products)
        'stocks' => $all_products
        ]);
        
    }

    function fetchStockByBranchId($id) {
        $this->authorize('stock_access');
        $deliverableIDs = Deliverable::where('branch_id',$id)->pluck('id');
        $invoiceIDs = Invoice::where('branch_id',$id)->pluck('id');
        $products = Product::whereHas('deliverables',function($q) use($deliverableIDs){
             $q->whereIn('deliverable_id',$deliverableIDs);
         })
        ->with('invoices', function($q) use($invoiceIDs){
             $q->whereIn('invoice_id',$invoiceIDs);
             }
        )->with('returnInvoices', function($q) use($invoiceIDs){
            $q->whereIn('invoice_id',$invoiceIDs);
            }
       )->get();
        $data = [];
    foreach ($products as $product) {
            $delIds =  Deliverable::notReturn()->where('branch_id',$id)->whereIn('id',$product->deliverables->pluck('deliverable_id'))->pluck('id');
            $reDelIds =  Deliverable::return()->where('branch_id',$id)->whereIn('id',$product->deliverables->pluck('deliverable_id'))->pluck('id');
            $invoices_qty = $product->invoices->sum('qty');
            $return_invoices_qty = $product->returnInvoices->sum('qty');
            $product_data =  [
                'title' => $product->name,
                'in_stock' => $product->deliverables ? 
                    DeliverableDetail::whereIn('deliverable_id',$delIds)->where('product_id',$product->id)->sum('qty') -  DeliverableDetail::whereIn('deliverable_id',$reDelIds)->where('product_id',$product->id)->sum('qty')
                    : 0,
                'out_stock' => $invoices_qty - $return_invoices_qty < 0 ? -($invoices_qty - $return_invoices_qty) : $invoices_qty - $return_invoices_qty,
                'total' => ( DeliverableDetail::whereIn('deliverable_id',$delIds)->where('product_id',$product->id)->sum('qty') -  DeliverableDetail::whereIn('deliverable_id',$reDelIds)->where('product_id',$product->id)->sum('qty')) - ($invoices_qty - $return_invoices_qty < 0 ? -($invoices_qty - $return_invoices_qty) : $invoices_qty - $return_invoices_qty)
            ];  
            
            array_push($data,$product_data);
        }
        return $data;
        // return response()->json( StockListResource::collection($products));
         
     }
}
