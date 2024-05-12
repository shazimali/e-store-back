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
       ])->get();
       return response()->json([
        'branches' => BranchListResource::collection($branches),
        'stocks' => StockListResource::collection($products)
        ]);
        
    }

    function fetchStockByBranchId($id) {
        $this->authorize('stock_access');
        $branches =  Branch::where('store_id',$id)->get();
        $deliverableIDs = Deliverable::where('branch_id',$id)->pluck('id');
        $invoiceIDs = Invoice::where('branch_id',$id)->pluck('id');
        $products = Product::whereHas('deliverables',function($q) use($deliverableIDs,$invoiceIDs){
             $q->whereIn('deliverable_id',$deliverableIDs);
         })
         ->with('invoices', function($q) use($invoiceIDs){
             $q->whereIn('invoice_id',$invoiceIDs);
             }
        )->get();
        $data = [];
    foreach ($products as $product) {
            $delIds =  Deliverable::NotReturn()->where('branch_id',$id)->whereIn('id',$product->deliverables->pluck('deliverable_id'))->pluck('id');
            $reDelIds =  Deliverable::Return()->where('branch_id',$id)->whereIn('id',$product->deliverables->pluck('deliverable_id'))->pluck('id');
            //  return DeliverableDetail::whereIn('deliverable_id',$delIds)->sum('qty') -  DeliverableDetail::whereIn('deliverable_id',$reDelIds)->sum('qty');
            $product_data =  [
                'title' => $product->name,
                'in_stock' => $product->deliverables ? 
                    DeliverableDetail::whereIn('deliverable_id',$delIds)->where('product_id',$product->id)->sum('qty') -  DeliverableDetail::whereIn('deliverable_id',$reDelIds)->where('product_id',$product->id)->sum('qty')
                    : 0,
                'out_stock' => $product->invoices ? $product->invoices->sum('qty'): 0,
                'total' => ( DeliverableDetail::whereIn('deliverable_id',$delIds)->where('product_id',$product->id)->sum('qty') -  DeliverableDetail::whereIn('deliverable_id',$reDelIds)->where('product_id',$product->id)->sum('qty')) - $product->invoices->sum('qty')
            ];  
            
            array_push($data,$product_data);
        }
        return $data;
        // return response()->json( StockListResource::collection($products));
         
     }
}
