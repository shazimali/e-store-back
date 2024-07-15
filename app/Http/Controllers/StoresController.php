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
use App\Models\Product;
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
            'sales' => DB::select("call store_invoiced_sale($id)"),
            'purchases' => $this->invoicedPurchase($id),
            'profit' => $this->invoicedSales($id) - $this->invoicedPurchase($id),
            'discounts' => Discount::where('store_id',$id)->sum('amount'),
            'sales_invoices' =>  Invoice::where('store_id',$id)->count(),
            'deliverables' => Deliverable::notReturn()->where('store_id',$id)->sum('total_qty'),
            'return_deliverables' => Deliverable::return()->where('store_id',$id)->sum('total_qty'),
            'debit' => Voucher::where('store_id',$id)->sum('amount') - Discount::where('store_id',$id)->sum('amount'),
            'credit' => Invoice::where('store_id',$id)->sum('total_price') - Voucher::where('store_id',$id)->sum('amount'),
            'branches' => Branch::where('store_id',$id)->count(),
            'invoiced_sale' => DB::select("call store_invoiced_sale($id)"),
            'invoiced_purchase' => DB::select("call store_invoiced_purchase($id)"),
        ],
        'yearly_states' => [
            'sales' => DB::select("call store_invoiced_sale($id)"),// Invoice::where('store_id',$id)->whereYear('date',Carbon::now())->sum('total_price') - Discount::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount'),
            'purchases' => $this->currentYearInvoicedPurchase($id),
            'profit' => $this->currentYearInvoicedSales($id) - $this->currentYearInvoicedPurchase($id),
            'discounts' => Discount::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount'),
            'sales_invoices' => Invoice::where('store_id',$id)->whereYear('date',Carbon::now())->count(),
            'deliverables' => Deliverable::notReturn()->where('store_id',$id)->whereYear('date',Carbon::now())->sum('total_qty'),
            'return_deliverables' => Deliverable::return()->where('store_id',$id)->whereYear('date',Carbon::now())->sum('total_qty'),
            'debit' => Voucher::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount') - Discount::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount'),
            'credit' => Invoice::where('store_id',$id)->whereYear('date',Carbon::now())->sum('total_price') - Voucher::where('store_id',$id)->whereYear('date',Carbon::now())->sum('amount'),
            'branches' => Branch::where('store_id',$id)->whereYear('created_at',Carbon::now())->count(),
            'invoiced_sale' => DB::select("call store_yearly_invoiced_sale($id)"),
            'invoiced_purchase' => DB::select("call store_invoiced_purchase($id)"),
        ],
        'monthly_states' => [
            'sales' =>DB::select("call store_invoiced_sale($id)"),// Invoice::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('total_price') - Discount::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount'),
            'purchases' => $this->currentMonthInvoicedPurchase($id),
            'profit' => $this->currentMonthInvoicedSales($id) - $this->currentMonthInvoicedPurchase($id),
            'discounts' => Discount::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount'),
            'sales_invoices' => Invoice::where('store_id',$id)->whereMonth('date',Carbon::now())->count(),
            'deliverables' => Deliverable::notReturn()->where('store_id',$id)->whereMonth('date',Carbon::now())->sum('total_qty'),
            'return_deliverables' => Deliverable::return()->where('store_id',$id)->whereMonth('date',Carbon::now())->sum('total_qty'),
            'debit' => Voucher::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount') - Discount::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount'),
            'credit' => Invoice::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('total_price') - Voucher::where('store_id',$id)->whereMonth('date',Carbon::now())->sum('amount'),
            'branches' => Branch::where('store_id',$id)->whereMonth('created_at',Carbon::now())->count(),
            'invoiced_sale' => DB::select("call store_monthly_invoiced_sale($id)"),
            'invoiced_purchase' => DB::select("call store_invoiced_purchase($id)"),
        ],
        'store_name' => Store::where('id',$id)->first()->name,
        'lstBranches' => DB::select("call store_branches_sale($id)"),
        'yearly_sales' =>  DB::select("call store_yearly_sales($id)"),
        'top_branch' => DB::select("call heigh_branch_sale($id)"),
        'monthly_sales' => DB::select("call store_monthly_sales($id)"),
        'yearly_deliverables' => DB::select("call store_yearly_deliverables($id)"),
        'monthly_deliverables' => DB::select("call store_monthly_deliverables($id)"),
    ]);
    } catch (\Throwable $th) {
        return  response()->json($th->getMessage());

    }
    

}

public function invoicedPurchase($store_id)  {
    //fetch all related invoices 
    $invoices_query =  DB::table('invoices')->where('store_id',$store_id)->get();
          
    //fetch invoiced products ID's
    $products_query  = DB::table('invoice_details')->whereIn('invoice_id',$invoices_query->pluck('id'));
    if(request('product_id')){
        $products_query->when(request('product_id'), function ($q) {
            return $q->where('product_id',request('product_id'));
        });
    }
    $invoiceIDs = $invoices_query->pluck('id');
    //fetch products details
    $products = Product::whereIn('id',$products_query->pluck('product_id'))
        ->with(['invoices' => function($q) use($invoiceIDs) {
                $q->whereIn('invoice_id',$invoiceIDs);
        }])
        ->with(['returnInvoices' => function($q) use($invoiceIDs) {
            $q->whereIn('invoice_id',$invoiceIDs);
        }])
        ->with('purchases')
        ->get();

    $purchases = 0;
    foreach ($products as $key => $pr_item) {
      $mean_price = $pr_item->purchases->sum('price')/$pr_item->purchases->count();
      $purchases += $pr_item->invoices->sum('qty') * $mean_price;  
      if($pr_item->returnInvoices->count()){
       $purchases -= $pr_item->returnInvoices->sum('qty') * $mean_price;      
      }    
    }

    return $purchases;
}

public function currentYearInvoicedPurchase($store_id)  {
    //fetch all related invoices 
    $invoices_query =  DB::table('invoices')->whereYear('date',Carbon::now()->year)->where('store_id',$store_id)->get();
          
    //fetch invoiced products ID's
    $products_query  = DB::table('invoice_details')->whereIn('invoice_id',$invoices_query->pluck('id'));
    if(request('product_id')){
        $products_query->when(request('product_id'), function ($q) {
            return $q->where('product_id',request('product_id'));
        });
    }
    $invoiceIDs = $invoices_query->pluck('id');
    //fetch products details
    $products = Product::whereIn('id',$products_query->pluck('product_id'))
        ->with(['invoices' => function($q) use($invoiceIDs) {
                $q->whereIn('invoice_id',$invoiceIDs);
        }])
        ->with(['returnInvoices' => function($q) use($invoiceIDs) {
            $q->whereIn('invoice_id',$invoiceIDs);
        }])
        ->with('purchases')
        ->get();

    $purchases = 0;
    foreach ($products as $key => $pr_item) {
      $mean_price = $pr_item->purchases->sum('price')/$pr_item->purchases->count();
      $purchases += $pr_item->invoices->sum('qty') * $mean_price;  
      if($pr_item->returnInvoices->count()){
       $purchases -= $pr_item->returnInvoices->sum('qty') * $mean_price;      
      }    
    }

    return $purchases;
}
public function currentMonthInvoicedPurchase($store_id)  {
    //fetch all related invoices 
    $invoices_query =  DB::table('invoices')->whereMonth('date',Carbon::now()->month)->where('store_id',$store_id)->get();
          
    //fetch invoiced products ID's
    $products_query  = DB::table('invoice_details')->whereIn('invoice_id',$invoices_query->pluck('id'));
    if(request('product_id')){
        $products_query->when(request('product_id'), function ($q) {
            return $q->where('product_id',request('product_id'));
        });
    }
    $invoiceIDs = $invoices_query->pluck('id');
    //fetch products details
    $products = Product::whereIn('id',$products_query->pluck('product_id'))
        ->with(['invoices' => function($q) use($invoiceIDs) {
                $q->whereIn('invoice_id',$invoiceIDs);
        }])
        ->with(['returnInvoices' => function($q) use($invoiceIDs) {
            $q->whereIn('invoice_id',$invoiceIDs);
        }])
        ->with('purchases')
        ->get();

    $purchases = 0;
    foreach ($products as $key => $pr_item) {
      $mean_price = $pr_item->purchases->sum('price')/$pr_item->purchases->count();
      $purchases += $pr_item->invoices->sum('qty') * $mean_price;  
      if($pr_item->returnInvoices->count()){
       $purchases -= $pr_item->returnInvoices->sum('qty') * $mean_price;      
      }    
    }

    return $purchases;
}

public function invoicedSales($store_id)  {
    //fetch all related invoices 
    $invoices_query =  DB::table('invoices')->where('store_id',$store_id)->get();
          
    //fetch invoiced products ID's
    $products_query  = DB::table('invoice_details')->whereIn('invoice_id',$invoices_query->pluck('id'));
    if(request('product_id')){
        $products_query->when(request('product_id'), function ($q) {
            return $q->where('product_id',request('product_id'));
        });
    }
    //fetch discounts details
    $invoiceIDs = $invoices_query->pluck('id');
    //fetch products details

    $products = Product::whereIn('id',$products_query->pluck('product_id'))
    ->with(['invoices' => function($q) use($invoiceIDs) {
            $q->whereIn('invoice_id',$invoiceIDs);
    }])
    ->with(['returnInvoices' => function($q) use($invoiceIDs) {
        $q->whereIn('invoice_id',$invoiceIDs);
    }])
    ->with('purchases')
    ->get();

    $sales = 0;
    foreach ($products as $key => $pr_item) {
      $mean_price = $pr_item->invoices->sum('price')/$pr_item->invoices->count();
      $sales += $pr_item->invoices->sum('qty') * $mean_price;  
      if($pr_item->returnInvoices->count()){
       $sales -= $pr_item->returnInvoices->sum('qty') * $mean_price;      
      }    
    }

    return $sales;
}

public function currentYearInvoicedSales($store_id)  {
    //fetch all related invoices 
    $invoices_query =  DB::table('invoices')->whereYear('date',Carbon::now()->year)->where('store_id',$store_id)->get();
          
    //fetch invoiced products ID's
    $products_query  = DB::table('invoice_details')->whereIn('invoice_id',$invoices_query->pluck('id'));
    if(request('product_id')){
        $products_query->when(request('product_id'), function ($q) {
            return $q->where('product_id',request('product_id'));
        });
    }
    //fetch discounts details
    $invoiceIDs = $invoices_query->pluck('id');
    //fetch products details

    $products = Product::whereIn('id',$products_query->pluck('product_id'))
    ->with(['invoices' => function($q) use($invoiceIDs) {
            $q->whereIn('invoice_id',$invoiceIDs);
    }])
    ->with(['returnInvoices' => function($q) use($invoiceIDs) {
        $q->whereIn('invoice_id',$invoiceIDs);
    }])
    ->with('purchases')
    ->get();

    $sales = 0;
    foreach ($products as $key => $pr_item) {
      $mean_price = $pr_item->invoices->sum('price')/$pr_item->invoices->count();
      $sales += $pr_item->invoices->sum('qty') * $mean_price;  
      if($pr_item->returnInvoices->count()){
       $sales -= $pr_item->returnInvoices->sum('qty') * $mean_price;      
      }    
    }

    return $sales;
}

public function currentMonthInvoicedSales($store_id)  {
    //fetch all related invoices 
    $invoices_query =  DB::table('invoices')->whereMonth('date',Carbon::now()->month)->where('store_id',$store_id)->get();
          
    //fetch invoiced products ID's
    $products_query  = DB::table('invoice_details')->whereIn('invoice_id',$invoices_query->pluck('id'));
    if(request('product_id')){
        $products_query->when(request('product_id'), function ($q) {
            return $q->where('product_id',request('product_id'));
        });
    }
    //fetch discounts details
    $invoiceIDs = $invoices_query->pluck('id');
    //fetch products details

    $products = Product::whereIn('id',$products_query->pluck('product_id'))
    ->with(['invoices' => function($q) use($invoiceIDs) {
            $q->whereIn('invoice_id',$invoiceIDs);
    }])
    ->with(['returnInvoices' => function($q) use($invoiceIDs) {
        $q->whereIn('invoice_id',$invoiceIDs);
    }])
    ->with('purchases')
    ->get();

    $sales = 0;
    foreach ($products as $key => $pr_item) {
      $mean_price = $pr_item->invoices->sum('price')/$pr_item->invoices->count();
      $sales += $pr_item->invoices->sum('qty') * $mean_price;  
      if($pr_item->returnInvoices->count()){
       $sales -= $pr_item->returnInvoices->sum('qty') * $mean_price;      
      }    
    }

    return $sales;
}

}
