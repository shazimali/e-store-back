<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliverableRequest;
use App\Http\Resources\BranchListResource;
use App\Http\Resources\DeliverableEditResource;
use App\Http\Resources\DeliverableListResource;
use App\Http\Resources\DeliverableShowResource;
use App\Http\Resources\Products\ProductsListResource;
use App\Http\Resources\StoreListResource;
use App\Models\Branch;
use App\Models\Deliverable;
use App\Models\DeliverableDetail;
use App\Models\Product;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliverablesController extends Controller
{
    function index(Request $request) {
        $this->authorize('purchase_access');
        $deliverables = Deliverable::paginate($request->item_per_page);
        
        if($request->search){
            $deliverables = Deliverable::where('store_id','LIKE',"%{$request->search}%")
            ->orWhere('total_qty','LIKE',"%{$request->search}%")
            ->orWhere('date','LIKE',"%{$request->search}%")
            ->paginate($request->item_per_page);
        }

        return DeliverableListResource::collection($deliverables);
    }

    function create() {
        $this->authorize('purchase_create');
        return response()->json(StoreListResource::collection(Store::all()));
    }

    function fetchStoreBranchesProducts($id) {
        $this->authorize('purchase_create');
        return response()->json([
            'products' => ProductsListResource::collection(Product::where('store_id',$id)->get()),
            'branches' => BranchListResource::collection(Branch::where('store_id',$id)->get())
        ]); 
    }

    function store(StoreDeliverableRequest $request) {
        $this->authorize('purchase_save');
        $now = Carbon::now();
        $data = $request->all();
        $data['invoice_id'] =  Deliverable::count() > 0 ?  Deliverable::latest('id')->first()->invoice_id+1 :1;
        $data['invoice_prefix'] =  $now->year;
        $data['invoice_sr_number'] =  $data['invoice_prefix'].str_pad($data['invoice_id'],5,'0',STR_PAD_LEFT);
        $deliverable = Deliverable::create($data);

        foreach ($request->products as $key => $product) {
            DeliverableDetail::create([
                'deliverable_id' => $deliverable->id,
                'product_id' => $product['id'],
                'qty' => $product['qty'],
                'price' => $product['price'],
                'date' => $request['date']
            ]);
        }
        return  response()->json($deliverable->id);
    }

    function fetchDeliverableById($id) {
        return new DeliverableShowResource(Deliverable::where('id',$id)->first());
    }

    function edit($id) {
        $this->authorize('purchase_edit');
        try {
            return new  DeliverableEditResource(Deliverable::where('id',$id)->with('details')->first());
        } catch (\Throwable $th) {
            return  response()->json(['error' => 'Error in getting deliverable.'],422);
        }

        
    }

    function update(StoreDeliverableRequest $request, $id) {
        $this->authorize('purchase_edit');
        
        $purchase = Deliverable::where('id',$id)->first();
        $purchase->update([
                "date" => $request->date,
                "total_qty" => $request->total_qty,
                "total_price" => $request->total_price,
        ]);

        DeliverableDetail::where('purchase_id',$purchase->id)->delete();
        
        foreach ($request->products as $key => $product) {
            DeliverableDetail::create([
                'purchase_id' => $purchase->id,
                'product_id' => $product['id'],
                'qty' => $product['qty'],
                'price' => $product['price'],
                'date' => $request['date']
            ]);
        }

        return  response()->json('Deliverable updated successfully!');
    }
}
