<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\InvoiceListResoruce;
use App\Http\Resources\InvoiceShowResource;
use App\Http\Resources\ProductInvoiceShowResource;
use App\Http\Resources\Products\ProductsEditResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\CompanyDetail;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    function index(Request $request) {
        $this->authorize('deliverable_access');
        $invoices = Invoice::paginate($request->item_per_page);
        
        if($request->search){
            $invoices = Invoice::where('store_id','LIKE',"%{$request->search}%")
            ->orWhere('total_qty','LIKE',"%{$request->search}%")
            ->orWhere('total_price','LIKE',"%{$request->search}%")
            ->orWhere('date','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        return InvoiceListResoruce::collection($invoices);
    }

    function create() {
        $this->authorize('deliverable_create');
        return response()->json(StoreListResource::collection(Store::all()));
    }

    function fetchStoreBranchesProducts($id) {
        $this->authorize('deliverable_create');
        return response()->json([
            'products' => ProductInvoiceShowResource::collection(Product::where('store_id',$id)->get()),
            'branches' => BranchListResource::collection(Branch::where('store_id',$id)->get())
        ]); 
    }

    function store(StoreInvoiceRequest $request) {
        $this->authorize('deliverable_save');
        $data = $request->all();
        $data['invoice_id'] =  Invoice::count() > 0 ?  Invoice::latest('id')->first()->invoice_id+1 :3000;
        $invoice = Invoice::create($data);

        foreach ($request->products as $key => $product) {
            InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product['id'],
                'qty' => $product['qty'],
                'price' => $product['price'],
                'sale_tax' => $product['sale_tax'],
                'ext_tax' => $product['ext_tax'],
                'date' => $request['date']
            ]);
        }
        return  response()->json($invoice->id);
    }

    function fetchInvoiceById($id) {
        return new InvoiceShowResource(Invoice::where('id',$id)->first());
    }

    // function edit($id) {
    //     $this->authorize('purchase_edit');
    //     try {
    //         return new  DeliverableEditResource(Deliverable::where('id',$id)->with('details')->first());
    //     } catch (\Throwable $th) {
    //         return  response()->json(['error' => 'Error in getting deliverable.'],422);
    //     }

        
    // }

    // function update(UpdateDeliverableRequest $request, $id) {
    //     $this->authorize('deliverable_edit');
    //     $deliverable = Deliverable::where('id',$id)->first();
    //     $deliverable->update([
    //             "date" => $request->date,
    //             "order_date" => $request->order_date,
    //             "store_id" => $request->store_id,
    //             "branch_id" => $request->branch_id,
    //             "sr_number" => $request->sr_number,
    //             "remarks" => $request->remarks,
    //             "total_qty" => $request->total_qty
    //     ]);

    //     DeliverableDetail::where('deliverable_id',$deliverable->id)->delete();
        
    //     foreach ($request->products as $key => $product) {
    //         DeliverableDetail::create([
    //             'deliverable_id' => $deliverable->id,
    //             'product_id' => $product['id'],
    //             'qty' => $product['qty'],
    //             'price' => $product['price'],
    //             'date' => $request['date']
    //         ]);
    //     }

    //     return  response()->json('Deliverable updated successfully!');
    // }
}
