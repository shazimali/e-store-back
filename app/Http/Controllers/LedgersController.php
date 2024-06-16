<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchListResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgersController extends Controller
{
    function index(Request $request) {
        $store_name = isset($request->store_id) &&  $request->store_id ? Store::where('id',$request->store_id)->first()->name : '';
        $branch_name = isset($request->branch_id) && $request->branch_id ? Branch::where('id',$request->branch_id)->first()->name : '';
        $vouchers = Voucher::select('id','amount','date','invoice_id as data_id')
                    ->where('store_id',$request->store_id);
        $discounts = Discount::select('id','amount','date','id as data_id')
                    ->where('store_id',$request->store_id);
        $invoices =  Invoice::select('id','total_price as amount','date','invoice_id as data_id')
                    ->where('store_id',$request->store_id);

        if($request->branch_id){
            $invoices->where('branch_id',$request->branch_id);
            $vouchers->where('branch_id',$request->branch_id);
            $discounts->where('branch_id',$request->branch_id);
        }
        if($request->from_date){
            $invoices->whereDate('date','>=',$request->from_date);
            $vouchers->whereDate('date','>=',$request->from_date);
            $discounts->whereDate('date','>=',$request->from_date);
        }
        if($request->to_date){
            $invoices->whereDate('date','<=',$request->to_date);
            $vouchers->whereDate('date','<=',$request->to_date);
            $discounts->whereDate('date','<=',$request->to_date);
        }
        // $data =  $invoices->get()->concat($vouchers->get());
        // return $data->concat($discounts->get())->sortBy('date');
        // return $data->concat($discounts);
        $invoices_data = $invoices ->get()->toArray();
        $vouchers_data = $vouchers->get()->toArray();
        $discount_data = $discounts->get()->toArray();
        $data = array_merge($invoices_data, $vouchers_data, $discount_data);
        return response()->json([
            'data' =>  $data,
            'store_name' =>  $store_name,
            'branch_name' =>  $branch_name
        ]);
    }

    function fetchStores() {
        return response()->json( StoreListResource::collection(Store::all())); 
    }

    function fetchBranchesByStoreID($store_id) {
        return response()->json( BranchListResource::collection(Branch::where('store_id',$store_id)->get())); 
    }

    function profitLoss(Request $request)  {
        
        //fetch all related invoices 
        $invoices_query =  DB::table('invoices')->where('store_id',$request->store_id);
        if(request('branch_id')){
            $invoices_query->when(request('branch_id'), function ($q) {
                return $q->where('branch_id',request('branch_id'));
            });
        }        
        
        //fetch invoiced products ID's
        $products_query  = DB::table('invoice_details')->whereIn('invoice_id',$invoices_query->pluck('id'));
        if(request('product_id')){
            $products_query->when(request('product_id'), function ($q) {
                return $q->where('product_id',request('product_id'));
            });
        }
        //fetch products details
       return Product::whereIn('id',$products_query->pluck('product_id'))
                        ->with('invoices','purchases','returnInvoices')
                        ->paginate($request->item_per_page);

    }
}
