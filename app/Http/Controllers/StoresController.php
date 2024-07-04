<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\City;
use App\Models\Deliverable;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Store;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoresController extends Controller
{
    function index(Request $request) {
        $this->authorize('store_access');
        $stores = Store::with('city')->paginate($request->item_per_page);
        
        if($request->search){
            $stores = Store::where('name','LIKE',"%{$request->search}%")
            ->orWhere('code','LIKE',"%{$request->search}%")
            ->orWhere('email','LIKE',"%{$request->search}%")
            ->orWhere('phone','LIKE',"%{$request->search}%")
            ->orWhere('status','LIKE',"%{$request->search}%")
            ->orWhere('ntn','LIKE',"%{$request->search}%")
            ->orWhere('sale_tax_number','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }
        return StoreListResource::collection($stores);
    }

    function create(){
        $this->authorize('store_create');
        try {
            return response()->json(City::get());
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);            
        }
    }

    function save(SaveStoreRequest $request){
        $this->authorize('store_save');
        try {
            Store::create($request->all());
            return response()->json('Store saved successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);            
        }
    }

    function edit($id){
        $this->authorize('store_edit');
        try {
            $store = Store::find($id);
            return  response()->json($store);
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving product.'],422);
        }
    }

function update(UpdateStoreRequest $request, $id)   {
        $this->authorize('store_edit');
        try {
            $product = Store::find($id);
            $product->update($request->all());
            return  response()->json('Store updated successfully.');
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in saving store.'],422);
        }
    }
function dashboardByStoreId($store_id){
    $id = (int)$store_id;
    try {
         
    return  response()->json([
        'total_states' => [
            'sales' => Invoice::where('store_id',$id)->sum('total_price') - Discount::where('store_id',$id)->sum('amount'),
            'sales_invoices' => Invoice::where('store_id',$id)->count(),
            'deliverables' => Deliverable::notReturn()->where('store_id',$id)->sum('total_qty'),
            'return_deliverables' => Deliverable::return()->where('store_id',$id)->sum('total_qty'),
            'debit' => Voucher::where('store_id',$id)->sum('amount') - Discount::where('store_id',$id)->sum('amount'),
            'credit' => Invoice::where('store_id',$id)->sum('total_price') - Voucher::where('store_id',$id)->sum('amount'),
            'branches' => Branch::where('store_id',$id)->count(),
        ],
        'yearly_states' => [
            'sales' => Invoice::where('store_id',$id)->whereYear('date',Carbon::now())->sum('total_price') - Discount::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount'),
            'sales_invoices' => Invoice::where('store_id',$id)->whereYear('date',Carbon::now())->count(),
            'deliverables' => Deliverable::notReturn()->where('store_id',$id)->whereYear('date',Carbon::now())->sum('total_qty'),
            'return_deliverables' => Deliverable::return()->where('store_id',$id)->whereYear('date',Carbon::now())->sum('total_qty'),
            'debit' => Voucher::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount') - Discount::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount'),
            'credit' => Invoice::where('store_id',$id)->whereYear('date',Carbon::now())->sum('total_price') - Voucher::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount'),
            'branches' => Branch::where('store_id',$id)->whereYear('created_at',Carbon::now())->count(),
        ],
        'monthly_states' => [
            'sales' => Invoice::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('total_price') - Discount::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount'),
            'sales_invoices' => Invoice::where('store_id',$id)->whereMonth('date',Carbon::now())->count(),
            'deliverables' => Deliverable::notReturn()->where('store_id',$id)->whereMonth('date',Carbon::now())->sum('total_qty'),
            'return_deliverables' => Deliverable::return()->where('store_id',$id)->whereMonth('date',Carbon::now())->sum('total_qty'),
            'debit' => Voucher::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount') - Discount::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount'),
            'credit' => Invoice::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('total_price') - Voucher::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount'),
            'branches' => Branch::where('store_id',$id)->whereMonth('created_at',Carbon::now())->count(),
        ],
        'store_name' => Store::where('id',$id)->first()->name,
        'yearly_sales' =>  DB::select("call yearly_sales($id)"),
        'monthly_sales' => DB::select("call monthly_sales($id)"),
        'yearly_deliverables' => DB::select("call yearly_deliverables($id)"),
        'monthly_deliverables' => DB::select("call monthly_deliverables($id)"),
    ]);
    } catch (\Throwable $th) {
        return  response()->json($th->getMessage());

    }
    

}
}
