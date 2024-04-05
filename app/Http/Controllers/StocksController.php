<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchListResource;
use App\Http\Resources\StockListResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\Deliverable;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class StocksController extends Controller
{
    function index(Request $request) {
        return response()->json( StoreListResource::collection(Store::all())); 
    }

    function fetchBranchesByStoreId($id) {
       $branches =  Branch::where('store_id',$id)->get();
       $deliverableIDs = Deliverable::where('store_id',$id)->pluck('id');
       $invoiceIDs = Invoice::where('store_id',$id)->pluck('id');
       $products = Product::whereHas('deliverables', function($q) use($deliverableIDs,$invoiceIDs){
            $q->whereIn('deliverable_id',$deliverableIDs);
        })
        ->whereHas('invoices', function($q) use($invoiceIDs){
            $q->whereIn('invoice_id',$invoiceIDs);
            }
       )->get();
       return response()->json([
        'branches' => BranchListResource::collection($branches),
        'stocks' => StockListResource::collection($products)
        ]);
        
    }

    function fetchStockByBranchId($id) {
        $branches =  Branch::where('store_id',$id)->get();
        $deliverableIDs = Deliverable::where('branch_id',$id)->pluck('id');
        $invoiceIDs = Invoice::where('branch_id',$id)->pluck('id');
        $products = Product::whereHas('deliverables', function($q) use($deliverableIDs,$invoiceIDs){
             $q->whereIn('deliverable_id',$deliverableIDs);
         })
         ->whereHas('invoices', function($q) use($invoiceIDs){
             $q->whereIn('invoice_id',$invoiceIDs);
             }
        )->get();
        return response()->json( StockListResource::collection($products));
         
     }
}
